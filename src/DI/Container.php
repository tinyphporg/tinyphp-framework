<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Container.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:52:14
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:52:14 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI;

use Tiny\DI\Definition\DefinitionInterface;
use Tiny\DI\Definition\Resolver\DefinitionResolverInterface;
use Tiny\DI\Injection\Injection;
use Tiny\DI\Definition\Provider\DefinitionProviderInterface;
use Tiny\DI\Definition\Resolver\ResolverDispatcher;
use Tiny\DI\Definition\CallableDefinition;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\Definition\InstanceDefinition;
use Tiny\DI\Definition\Provider\DefinitionProvider;

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
     * @var DefinitionInterface
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
    public function __construct(DefinitionProviderInterface $defintionProvider = null, ContainerInterface $wrapperContainer = null)
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
     * @param string $name 类名
     * @param mixed $value 类的实例
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
        
        // 是否已定义
        $definition = false !== strpos($className, '@anonymous') ? $this->definitionSource->getDefinition($className) : $this->getDefinition($className);
        if ($definition instanceof ObjectDefinition) {
            return $instance;
        }
        
        // 实例定义
        $instanceDefinition = new InstanceDefinition($className, $instance);
        if (false === strpos($className, '@anonymous') && !key_exists($className, $this->entriesBeingResolved)) {
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
     * @return DefinitionProvider
     */
    protected function createDefintionProvider()
    {
        return new DefinitionProvider();
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
?>