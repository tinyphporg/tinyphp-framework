<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Container.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月26日上午11:32:43 0 第一次建立该文件
 *          King 2021年11月26日上午11:32:43 1 修改
 *          King 2021年11月26日上午11:32:43 stable 1.0.01 审定
 */
namespace Tiny\DI;

use Tiny\DI\Definition\DefintionProivder;
use Tiny\DI\Definition\DefinitionInterface;
use Tiny\DI\Definition\DefinitionResolverInterface;
use Tiny\DI\Definition\DefinitionProviderInterface;
use Tiny\DI\Definition\ResolverDispatcher;
use Tiny\DI\Definition\CallableDefinition;
use Tiny\DI\Definition\NotFoundClassException;
use Tiny\DI\Injection\InjectionInterface;
use Tiny\DI\Injection\Injection;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\Definition\InstanceDefinition;
use Tiny\DI\Definition\DefinitionProivder;

/**
 * 容器接口
 *
 * @package Tiny.DI
 * @since 2021年11月26日上午11:32:43
 * @final 2021年11月26日上午11:32:43
 *       
 */
interface ContainerInterface
{
    
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $name Identifier of the entry to look for.
     *       
     * @throws NotFoundException No entry was found for **this** identifier.
     * @throws ContainerException while retrieving the entry.
     *        
     * @return mixed Entry.
     */
    public function get(string $name);
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($name)` returning true does not mean that `get($name)` will not throw an exception.
     * It does however mean that `get($name)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $name Identifier of the entry to look for.
     *       
     * @return bool
     */
    public function has(string $name): bool;
}

/**
 * 派发器接口
 *
 * @package Tiny.DI
 * @since 2022年1月4日下午6:24:46
 * @final 2022年1月4日下午6:24:46
 */
interface InvokerInterface
{
}

/**
 *
 * 容器类
 *
 * @package Tiny.Container
 * @since 2021年11月26日上午11:32:43
 * @final 2021年11月26日上午11:32:43
 *       
 */
class Container implements ContainerInterface, InvokerInterface
{
    
    /**
     * 已经解析的容器字典
     *
     * @var array
     */
    protected $resolvedEntries = [];
    
    /**
     * 执行容器
     *
     * @var ContainerInterface
     */
    protected $delegateContainer;
    
    /**
     * 定义提供者
     *
     * @var DefinitionProivder
     */
    protected $defintionProvider;
    
    /**
     * 已经获取的定义器字典
     *
     * @var array
     */
    protected $fetchedDefinitions = [];
    
    /**
     * 定义解析器
     *
     * @var DefinitionResolverInterface
     */
    protected $definitionResolver;
    
    /**
     *
     * @var Invoker
     */
    protected $invoker;
    
    /**
     *
     * @var Injection
     */
    protected $injection;
    
    /**
     * 
     * @var array
     */
    protected $entriesBeingResolved = [];
    
    /**
     * 
     * @var array
     */
    protected $entriesAlias = [];
    
    /**
     * 构造函数
     *
     * @param DefinitionProviderInterface $defintionProvider
     * @param ContainerInterface $wrapperContainer
     * @param InvokerInterface $invokerFactory
     */
    public function __construct(DefinitionProviderInterface $defintionProvider = null,
        ContainerInterface $wrapperContainer = null)
    {
        // 定义源
        $this->defintionProvider = $defintionProvider ?: $this->createDefineProvider();
        
        // 执行容器
        $this->delegateContainer = $wrapperContainer ?: $this;
        
        // 注入器
        $this->injection = new Injection($this);
        
        // 定义解析器
        $this->definitionResolver = new ResolverDispatcher($this->delegateContainer, $this->injection);
        
        // 预定义
        $this->resolvedEntries = [
            self::class => $this,
            ContainerInterface::class => $this->delegateContainer,
            InvokerInterface::class => $this
        ];
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\ContainerInterface::get()
     */
    public function get(string $name)
    {
        // 如果已解析则返回
        if (isset($this->resolvedEntries[$name]) || key_exists($name, $this->resolvedEntries)) {
            return $this->resolvedEntries[$name];
        }
        
        // 根据名称查找实例定义
        $definition = $this->getDefinition($name);
        if (!$definition) {
            throw new NotFoundException(sprintf('No entry or class found for "%s"', $name));
        }
        
        // 解析并返回值
        $value = $this->resolveDefinition($definition);
        $this->resolvedEntries[$name] = $value;
        return $value;
    }
    
    /**
     * 设置容器
     *
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name, $value)
    {
        if ($value instanceof \Closure) {
            $value = new CallableDefinition($name, $value);
        }
        
        if ($value instanceof DefinitionInterface) {
            $value->setName($name);
            $this->setDefinition($name, $value);
        } else {
            $this->resolvedEntries[$name] = $value;
        }
    }
    
    /**
     *
     * {@inheritdoc}
     *
     * @see \Tiny\DI\ContainerInterface::has()
     */
    public function has(string $name): bool
    {
        // 如果已解析则返回
        if (isset($this->resolvedEntries[$name]) || key_exists($name, $this->resolvedEntries)) {
            return true;
        }
        
        // 根据名称查找实例定义
        if ($this->getDefinition($name)) {
            return true;
        }
        return false;
    }
    
    /**
     * 派发调用函数
     *
     * @param callable $callable
     * @param array $params
     * @return mixed
     */
    public function call($callable, array $params = [])
    {
        return $this->getInvoker()->call($callable, $params);
    }
    
    /**
     * 注入一个对象实例 如果不存在则加入容器
     * 
     * @param mixed $instance
     * @return mixed
     */
    public function injectOn($instance)
    {
        if (!$instance) {
            return $instance;
        }
        
        $className = get_class($instance);
       
        $definition = false !== strpos($className, '@anonymous')
        ? $this->definitionSource->getDefinition($className)
        : $this->getDefinition($className);
        if ($definition instanceof ObjectDefinition)
        {
            return $instance;
        }
        
        $instanceDefinition = new InstanceDefinition($className, $instance);
        if (false === strpos($className, '@anonymous')  && !key_exists($className, $this->entriesBeingResolved))
        {
            $this->setDefinition($className, $instanceDefinition);
        }
        $this->definitionResolver->resolve($instanceDefinition);
        
    }
    
    /**
     * 获取派发器实例
     *
     * @return \Tiny\DI\Invoker
     */
    protected function getInvoker()
    {
        if (!$this->invoker) {
            $this->invoker = new Invoker($this->delegateContainer, $this->injection);
        }
        return $this->invoker;
    }
    
    /**
     * 创建定义的提供源
     *
     * @return DefintionProivder
     */
    protected function createDefintionProvider()
    {
        return new DefinitionProivder();
    }
    
    /**
     * 根据id 获取定义
     *
     * @param string $name
     * @return DefinitionInterface
     */
    protected function getDefinition($name)
    {
        if (!key_exists($name, $this->fetchedDefinitions)) {
            $this->fetchedDefinitions[$name] = $this->defintionProvider->getDefinition($name);
        }
        return $this->fetchedDefinitions[$name];
    }
    
    /**
     * 设置定义类
     *
     * @param string $name
     * @param DefinitionInterface $definition
     */
    protected function setDefinition(string $name, DefinitionInterface $definition)
    {
        if (key_exists($name, $this->resolvedEntries)) {
            unset($this->resolvedEntries[$name]);
        }
        
        $this->fetchedDefinitions = [];
        return $this->defintionProvider->addDefinition($definition);
    }
    
    /**
     * 解析定义类
     *
     * @param DefinitionInterface $definition
     * @param array $parameters
     * @throws DependencyException
     * @return mixed
     */
    private function resolveDefinition(DefinitionInterface $definition, array $parameters = [])
    {
        $entryName = $definition->getName();
        
        if (isset($this->entriesBeingResolved[$entryName])) {
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$entryName'");
        }
        
        $this->entriesBeingResolved[$entryName] = true;
        try {
            $value = $this->definitionResolver->resolve($definition, $parameters);
        } finally {
            unset($this->entriesBeingResolved[$entryName]);
        }
        
        return $value;
    }
}

/**
 * 派发器
 *
 * @package Tiny.DI
 * @since 2022年1月4日下午7:47:04
 * @final 2022年1月4日下午7:47:04
 */
class Invoker
{
    
    /**
     * 容器实例
     *
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * 注入器实例
     *
     * @var Injection
     */
    private $injection;
    
    /**
     * 构造函数
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        $this->injection = $injection ?: new Injection($container);
    }
    
    /**
     * 调用回调函数
     *
     * @param callable $callable
     * @param array $parameters
     * @return mixed
     */
    public function call($callable, array $parameters = [])
    {
        $callable = $this->resolveCallable($callable);
        $callableReflection = $this->createCallableReflection($callable);
        if ($callableReflection instanceof \ReflectionMethod && !$callableReflection->isPublic())
        {
            $callableReflection->setAccessible(true);   
        }
        $args = $this->injection->getParameters($callableReflection, $parameters, []);
        return call_user_func_array($callable, $args);
    }
    
    /**
     * 创建回调实例的反射实例
     *
     * @param callable $callable
     * @throws NotCallableException
     * @return \ReflectionFunctionAbstract
     */
    public function createCallableReflection($callable): \ReflectionFunctionAbstract
    {

        // Closure
        if ($callable instanceof \Closure) {
            return new \ReflectionFunction($callable);
        }
        
        // Array callable
        if (is_array($callable)) {
            [
                $class,
                $method
            ] = $callable;
            
            if (!method_exists($class, $method)) {
                throw NotCallableException::fromInvalidCallable($callable);
            }
            
            return new \ReflectionMethod($class, $method);
        }
        
        // Callable object (i.e. implementing __invoke())
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            return new \ReflectionMethod($callable, '__invoke');
        }
        
        // Standard function
        if (is_string($callable) && function_exists($callable)) {
            return new \ReflectionFunction($callable);
        }
        
        throw new NotCallableException(
            sprintf('%s is not a callable', is_string($callable) ? $callable : 'Instance of ' . get_class($callable)));
    }
    
    /**
     * 解析回调函数
     *
     * @param callable $callable
     * @throws NotCallableException
     * @return \Closure|mixed
     */
    protected function resolveCallable($callable)
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable, 2);
        }
        
        $callable = $this->resolveFromContainer($callable);
        
        if (!is_callable($callable)) {
            throw new NotCallableException(
                sprintf('%s is not a callable',
                    is_object($callable) ? 'Instance of ' . get_class($callable) : var_export($callable, true)));
        }
        return $callable;
    }
    
    /**
     * 从容器实例中解析参数
     *
     * @param callable $callable
     * @throws NotFoundClassException
     * @throws NotCallableException
     * @return \Closure|mixed|\Closure|mixed
     */
    private function resolveFromContainer($callable)
    {
        // Shortcut for a very common use case
        if ($callable instanceof \Closure) {
            return $callable;
        }
        
        // If it's already a callable there is nothing to do
        if (is_callable($callable)) {
            // TODO with PHP 8 that should not be necessary to check this anymore
            if (!$this->isStaticCallToNonStaticMethod($callable)) {
                return $callable;
            }
        }
        
        // The callable is a container entry name
        if (is_string($callable)) {
            try {
                return $this->container->get($callable);
            } catch (NotFoundClassException $e) {
                throw $e;
            }
        }
        
        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && is_string($callable[0])) {
            try {
                // Replace the container entry name by the actual object
                $callable[0] = $this->container->get($callable[0]);
                return $callable;
            } catch (NotFoundClassException $e) {
                if ($this->container->has($callable[0])) {
                    throw $e;
                }
                throw new NotCallableException(
                    sprintf('Cannot call %s() on %s because it is not a class nor a valid container entry', $callable[1],
                        $callable[0]));
            }
        }
        
        // Unrecognized stuff, we let it fail later
        return $callable;
    }
    
    /**
     * 是否为静态回调
     *
     * @param callable $callable
     * @return bool
     */
    private function isStaticCallToNonStaticMethod($callable): bool
    {
        if (is_array($callable) && is_string($callable[0])) {
            [
                $class,
                $method
            ] = $callable;
            
            if (!method_exists($class, $method)) {
                return false;
            }
            
            $reflection = new \ReflectionMethod($class, $method);
            
            return !$reflection->isStatic();
        }
        
        return false;
    }
}
?>