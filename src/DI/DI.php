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
     * @throws NotFoundException  No entry was found for **this** identifier.
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
class Container implements ContainerInterface, FactoryInterface, InvokerInterface
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
    protected  $definitionResolver;
    
    /**
     * 
     * @var Invoker
     */
    protected $invoker;
    
    /**
     * 构造函数
     * 
     * @param DefinitionProviderInterface $defintionProvider
     * @param ContainerInterface $wrapperContainer
     * @param InvokerInterface $invokerFactory
     */
    public function __construct(
        DefinitionProviderInterface $defintionProvider = null,
        ContainerInterface $wrapperContainer = null,
        InvokerInterface $invokerFactory = null
    ) {
        $this->defintionProvider  = $defintionProvider ?: $this->createDefineProvider();
        $this->delegateContainer = $wrapperContainer ?: $this;
        $this->invoker = $invokerFactory ?: $this;
        
        $this->resolvedEntries = [
            self::class => $this,
            ContainerInterface::class => $this,
            FactoryInterface::class => $this,
            InvokerInterface::class => $this,  
        ];
    }
    
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $name Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for **this** identifier.
     * @throws ContainerException while retrieving the entry.
     *
     * @return mixed Entry.
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
        
        //解析并返回值
        $value = $this->resolveDefinition($definition);
        
        $this->resolvedEntries[$name] = $value;
        
        return $value;
    }
    
    public function set(string $name, $value)
    {
        if ($value instanceof DefinitionHelper) {
            $value = $value->getDefinition($name);
        } elseif ($value instanceof \Closure) {
            $value = new FactoryDefinition($name, $value);
        }
        
        if ($value instanceof ValueDefinition) {
            $this->resolvedEntries[$name] = $value->getValue();
        } elseif ($value instanceof Definition) {
            $value->setName($name);
            $this->setDefinition($name, $value);
        } else {
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
    
    protected function getInvoker()
    {
        if (!$this->invoker)
        {
            $this->invoker = new Invoker();
        }
        return $this->invoker;
    }
    
    /**
     * 创建定义的提供源
     * 
     * @return \Tiny\DI\DefintionProivder
     */
    protected function createDefintionProvider()
    {
        $provider = new DefintionProivder();
        $provider->setMutablDefintionProivder();
        return $provider;
    }
    
    /**
     * @param string $name
     *
     * @return Defintion|null
     */
    protected function getDefinition($name)
    {
        if (!key_exists($name, $this->fetchedDefinitions)) {
            $this->fetchedDefinitions[$name] = $this->defintionProvider->getDefinition($name);
        }
        
        return $this->fetchedDefinitions[$name];
    }
    
    protected function setDefinition(string $name, Defintion $definition)
    {
        if (key_exists($name, $this->resolvedEntries)) {
            unset($this->resolvedEntries[$name]);
        }
        
        $this->fetchedDefinitions = [];
        
        $this->defintionProvider->addDefinition($definition);
    }
    
}

class Invoker
{
    
}


interface DefinitionProviderInterface
{
    public function getDefinition($name);
    
    
    public function getDefinitions(): array;
    
}

class DefintionProivder
{
    
}


interface DefintionInterface
{
    
}

class Defintion
{
    
}

interface DefinitionResolverInterface
{
    
}

class ResolverDispatcher 
{
    
}
?>