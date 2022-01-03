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
use Tiny\DI\Autowiring\Autowiring;
use Tiny\DI\Autowiring\AutowiringInterface;
use Tiny\DI\Definition\NotFoundClassException;

/**
 * 容器接口
 *
 * @package Tiny.Container
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

interface FactoryInterface
{
}

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
     * @var DefintionProivder
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
     * @var Autowiring
     */
    protected $autowiring;
    

    /**
     * 构造函数
     *
     * @param DefinitionProviderInterface $defintionProvider
     * @param ContainerInterface $wrapperContainer
     * @param InvokerInterface $invokerFactory
     */
    public function __construct(DefinitionProviderInterface $defintionProvider = null, ContainerInterface $wrapperContainer = null)
    {
        $this->defintionProvider = $defintionProvider ?: $this->createDefineProvider();
        
        $this->delegateContainer = $wrapperContainer ?: $this;    
        
        $this->definitionResolver = new ResolverDispatcher($this->delegateContainer);
        
        $this->autowiring = new Autowiring($this);
        
        $this->resolvedEntries = [
            self::class => $this,
            ContainerInterface::class => $this,
            InvokerInterface::class => $this];
    }

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
    public function get(string $name)
    {
        // 如果已解析则返回
        if (isset($this->resolvedEntries[$name]) || key_exists($name, $this->resolvedEntries))
        {
            return $this->resolvedEntries[$name];
        }

        // 根据名称查找实例定义
        $definition = $this->getDefinition($name);
        if (! $definition)
        {
            throw new NotFoundException(sprintf('No entry or class found for "%s"', $name));
        }

        // 解析并返回值
        $value = $this->resolveDefinition($definition);
        $this->resolvedEntries[$name] = $value;

        return $value;
    }

    public function set(string $name, $value)
    {
        if ($value instanceof \Closure)
        {
            $value = new CallableDefinition($name, $value);
        }

        if ($value instanceof DefinitionInterface)
        {
            $value->setName($name);
            $this->setDefinition($name, $value);
        }
        else
        {
            $this->resolvedEntries[$name] = $value;
        }
    }

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
    public function has(string $name): bool
    {
        return TRUE;
    }

    public function call($callable, array $params = [])
    {
        return $this->getInvoker()->call($callable, $params);
    }
    
    public function getInvoker()
    {
        if (! $this->invoker)
        {
            $this->invoker = new Invoker($this, $this->autowiring);
        }
        return $this->invoker;
    }
    
   public function getAntowiring()
    {
        return $this->autowiring;
    }

    /**
     * 创建定义的提供源
     *
     * @return DefintionProivder
     */
    protected function createDefintionProvider()
    {
        $provider = new DefintionProivder();
        // $provider->setMutablDefintionProivder();
        return $provider;
    }

    /**
     *
     * @param string $name
     *
     * @return DefinitionInterface
     */
    protected function getDefinition($name)
    {
        if (! key_exists($name, $this->fetchedDefinitions))
        {
            $this->fetchedDefinitions[$name] = $this->defintionProvider->getDefinition($name);
        }

        return $this->fetchedDefinitions[$name];
    }

    protected function setDefinition(string $name, DefinitionInterface $definition)
    {
        if (key_exists($name, $this->resolvedEntries))
        {
            unset($this->resolvedEntries[$name]);
        }

        $this->fetchedDefinitions = [];

        $this->defintionProvider->addDefinition($definition);
    }

    private function resolveDefinition(DefinitionInterface $definition, array $parameters = [])
    {
        $entryName = $definition->getName();

        // Check if we are already getting this entry -> circular dependency
        if (isset($this->entriesBeingResolved[$entryName]))
        {
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$entryName'");
        }
        $this->entriesBeingResolved[$entryName] = true;

        // Resolve the definition
        try
        {
            $value = $this->definitionResolver->resolve($definition, $parameters);
        }
        finally {
            unset($this->entriesBeingResolved[$entryName]);
        }

        return $value;
    }
}



class Invoker
{
    
    /**
     * 
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * 
     * @var Autowiring
     */
    private $autowiring;
    
    /**
     * 
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, AutowiringInterface $autowiring)
    {
        $this->container = $container;
        $this->autowiring = $autowiring ?: new Autowiring($container);
    }

    public function call($callable, array $parameters = [])
    {
        if ($this->container)
        {
            $callable = $this->resolveCallable($callable);
        }

        $callableReflection = $this->createCallableReflection($callable);
        $args = $this->autowiring->getParameters($callableReflection, $parameters, []);
        return call_user_func_array($callable, $args);
    }
    
    public function createCallableReflection($callable): \ReflectionFunctionAbstract
    {
        // Closure
        if ($callable instanceof \Closure)
        {
            return new \ReflectionFunction($callable);
        }

        // Array callable
        if (is_array($callable))
        {
            [
                $class,
                $method] = $callable;

            if (! method_exists($class, $method))
            {
                throw NotCallableException::fromInvalidCallable($callable);
            }

            return new \ReflectionMethod($class, $method);
        }

        // Callable object (i.e. implementing __invoke())
        if (is_object($callable) && method_exists($callable, '__invoke'))
        {
            return new \ReflectionMethod($callable, '__invoke');
        }

        // Standard function
        if (is_string($callable) && function_exists($callable))
        {
            return new \ReflectionFunction($callable);
        }

        throw new NotCallableException(sprintf('%s is not a callable', is_string($callable) ? $callable : 'Instance of ' . get_class($callable)));
    }

    protected function resolveCallable($callable)
    {
        if (is_string($callable) && strpos($callable, '::') !== false)
        {
            $callable = explode('::', $callable, 2);
        }

        $callable = $this->resolveFromContainer($callable);

        if (! is_callable($callable))
        {
            throw new NotCallableException(sprintf('%s is not a callable', is_object($callable) ? 'Instance of ' . get_class($callable) : var_export($callable, true)));
        }
        return $callable;
    }

    private function resolveFromContainer($callable)
    {
        // Shortcut for a very common use case
        if ($callable instanceof \Closure)
        {
            return $callable;
        }

        // If it's already a callable there is nothing to do
        if (is_callable($callable))
        {
            // TODO with PHP 8 that should not be necessary to check this anymore
            if (! $this->isStaticCallToNonStaticMethod($callable))
            {
                return $callable;
            }
        }

        // The callable is a container entry name
        if (is_string($callable))
        {
            try
            {
                return $this->container->get($callable);
            }
            catch (NotFoundClassException $e)
            {
                throw $e;
            }
        }

        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && is_string($callable[0]))
        {
            try
            {
                // Replace the container entry name by the actual object
                $callable[0] = $this->container->get($callable[0]);
                return $callable;
            }
            catch (NotFoundClassException $e)
            {
                if ($this->container->has($callable[0]))
                {
                    throw $e;
                }
                throw new NotCallableException(sprintf('Cannot call %s() on %s because it is not a class nor a valid container entry', $callable[1], $callable[0]));
            }
        }

        // Unrecognized stuff, we let it fail later
        return $callable;
    }

    private function isStaticCallToNonStaticMethod($callable): bool
    {
        if (is_array($callable) && is_string($callable[0]))
        {
            [
                $class,
                $method] = $callable;

            if (! method_exists($class, $method))
            {
                return false;
            }

            $reflection = new \ReflectionMethod($class, $method);

            return ! $reflection->isStatic();
        }

        return false;
    }
}
?>