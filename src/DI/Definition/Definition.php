<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Definition.php
 * @author King
 * @version stable 1.0
 * @Date 2022年1月1日下午6:23:35
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2022年1月1日下午6:23:35 第一次建立该文件
 *          King 2022年1月1日下午6:23:35 修改
 *         
 */
namespace Tiny\DI\Definition;

use Tiny\DI\ContainerInterface;
use Tiny\DI\Injection\InjectionInterface;
use Tiny\DI\Injection\Injection;
use Tiny\DI\Invoker;

/**
 * 定义接口
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午4:41:48
 * @final 2022年1月4日下午4:41:48
 */
interface DefinitionInterface
{

    /**
     * 获取定义的名称
     *
     * @return string
     */
    public function getName(): string;

    /**
     * 设置定义的名称
     *
     * @param string $name
     */
    public function setName(string $name);
}

/**
 * 自解析的定义类
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午4:43:16
 * @final 2022年1月4日下午4:43:16
 */
interface SelfResolvingDefinition
{

    /**
     * 自解析
     *
     * @param ContainerInterface $container
     */
    public function resolve(ContainerInterface $container);

    /**
     * 是否可解析
     *
     * @param ContainerInterface $container
     * @return bool
     */
    public function isResolvable(ContainerInterface $container): bool;
}

/**
 * 定义提供者类接口
 *
 * @package Tiny.DI.Definition
 *         
 * @since 2022年1月4日下午4:45:59
 * @final 2022年1月4日下午4:45:59
 */
interface DefinitionProviderInterface
{

    /**
     * 根据名称获取定义
     *
     * @param string $name
     */
    public function getDefinition(string $name);

    /**
     * 获取所有定义
     *
     * @return array
     */
    public function getDefinitions(): array;
}

/**
 * 定义提供类
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午4:47:28
 * @final 2022年1月4日下午4:47:28
 */
class DefinitionProivder implements DefinitionProviderInterface
{

    /**
     * 所有定义提供者
     *
     * @var array
     */
    protected $definitionProivders = [];

    /**
     * 定义文件
     *
     * @var array
     */
    protected $definitionFiles = [];

    /**
     * 所有解析的定义
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * 构造函数
     *
     * @param array $definitionProivders
     *            预定义的定义提供实例
     */
    public function __construct(array $definitionProivders)
    {
        $this->definitionProivders = $definitionProivders;
    }
    
    /**
     * 增加
     * 
     * @param DefinitionProviderInterface $proivder
     */
    public function addDefinitionProivder(DefinitionProviderInterface $proivder)
    {
        $this->definitionProivders[] = $proivder;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\DefinitionProviderInterface::getDefinition()
     */
    public function getDefinition(string $name)
    {
        if (key_exists($name, $this->definitions)) {
            return $this->definitions[$name];
        }

        foreach ($this->definitionProivders as $proivder) {
            $definition = $proivder->getDefinition($name);
            if ($definition) {
                return $definition;
            }
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\DefinitionProviderInterface::getDefinitions()
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * 增加定义类实例
     *
     * @param DefinitionInterface $definition
     */
    public function addDefinition(DefinitionInterface $definition): bool
    {
        $name = $definition->getName();
        $this->definitions[$name] = $definition;
        return true;
    }

    /**
     * 增加一个定义文件
     *
     * @param string $path
     */
    public function addDefinitionFromPath($path)
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->addDefinitionFromPath($p);
            }
            return;
        }
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $this->addDefinitionFromPath($path . '/' . $file);
            }
            $this->definitionFiles[] = $path;
            return;
        }
        if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) == 'php') {
            if (! in_array($path, $this->definitionFiles)) {
                $definitions = require $path;
                if (! is_array($definitions)) {
                    return;
                }
                $this->addDefinitionFromArray($definitions);
                $this->definitionFiles[] = $path;
            }
        }
    }

    /**
     * 增加一个定义实例集合
     *
     * @param array $sourceDefinitions
     */
    public function addDefinitionFromArray(array $sourceDefinitions)
    {
        foreach ($sourceDefinitions as $name => $sourceDefinition) {
            
            if ($this->resolveSourceDefinitionItem($name, $sourceDefinition)) {
                continue;
            }
            $this->resloveSourceDefinition($name, $sourceDefinition);
        }
    }

    /**
     * 解析定义集合
     * int => ::class
     *
     * @param mixed $sourceDefinition
     * @return bool
     */
    public function resolveSourceDefinitionItem($name, $sourceDefinition): bool
    {
        if (! is_int($name)) {
            return false;
        }
        
        // 0 => definitionInterface 
        if ($sourceDefinition instanceof DefinitionInterface) {
            return $this->addDefinition($sourceDefinition);
        }
        
        // 0 => class name
        if (is_string($sourceDefinition) && false !== strpos($sourceDefinition, '\\')) {
            $name = $sourceDefinition;
            $objectDefinition = new ObjectDefinition($name, $sourceDefinition);
            return $this->addDefinition($objectDefinition);
        }
    }

    /**
     * 解析源定义 
     *  name => function(){}
     * @param string $name
     * @param mixed $sourceDefinition
     * @return bool
     */
    public function resloveSourceDefinition($name, $sourceDefinition):bool
    {
        if (!is_string($name))
        {
            return false;
        }
        
        // name => DefinitionInterface
        if ($sourceDefinition instanceof DefinitionInterface) {
            $sourceDefinition->setName($name);
            return $this->addDefinition($sourceDefinition);
        }
        
        // name => \Closeure || callable
        if ($sourceDefinition instanceof \Closure || is_callable($sourceDefinition)) {

            $definition = new CallableDefinition($name, $sourceDefinition);
            return $this->addDefinition($definition);
        }
        
        return false;
    }
}


/**
* 回调定义类
* 
* @package Tiny.DI.Definition
* @since 2022年1月4日下午5:08:15
* @final 2022年1月4日下午5:08:15
*/
class CallableDefinition implements DefinitionInterface
{

    /**
     * 回调函数实例
     * 
     * @var callable
     */
    protected $callable;

    /**
     * 定义名
     * 
     * @var string
     */
    protected $name;

    /**
     * 
     * @param string $name
     * @param callable $value
     */
    public function __construct($name, callable $callable)
    {
        $this->name = $name;
        $this->callable = $callable;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionInterface::setName()
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * 获取回调实例
     * 
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }
}

/**
* 
* @package namespace
* @since 2022年1月4日下午5:15:36
* @final 2022年1月4日下午5:15:36
*/
class ObjectDefinition implements DefinitionInterface
{
    /**
     * 定义名
     *
     * @var string
     */
    protected $name;

    /**
     * 类名
     * @var string
     */
    protected $className;

    public function __construct($name, $className)
    {
        $this->name = $name;
        $this->className = $className;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取类名
     * 
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionInterface::setName()
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}

/**
*  实例定义类
*  
* @package Tiny.DI.Definition
* @since 2022年1月4日下午11:29:58
* @final 2022年1月4日下午11:29:58
*/
class InstanceDefinition extends ObjectDefinition
{
    /**
     *  获取实例
     *  
     * @var mixed
     */
    private $instance;
    
    /**
     * 构造函数
     * @param string $name
     * @param object $instance
     */
    public function __construct($name, $instance)
    {
        $className = get_class($instance);
        $this->instance = $instance;
        parent::__construct($name, $className);
    }
    
    /**
     * 获取实例
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }
}

/**
*  定义解析接口
*  
* @package Tiny.DI.Definition
* @since 2022年1月4日下午5:19:23
* @final 2022年1月4日下午5:19:23
*/
interface DefinitionResolverInterface
{

    /**
     * 解析定义并返回解析后的值
     * 
     * @param DefinitionInterface $definition
     * @param array $parameters
     */
    public function resolve(DefinitionInterface $definition, array $parameters = []);

    /**
     * 是否可解析
     * 
     * @param DefinitionInterface $definition
     * @param array $parameters
     * @return bool
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool;
}

/**
* 定义解析派发器
* 
* @package Tiny.DI.Definition
* @since 2022年1月4日下午5:21:46
* @final 2022年1月4日下午5:21:46
*/
class ResolverDispatcher implements DefinitionResolverInterface
{

    /**
     * 容器实例
     * 
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 回调类解析器
     *  
     * @var CallableResolver
     */
    protected $callableResolver;
    
    /**
     * 对象解析器
     *  
     * @var ObjectResolver
     */
    protected $objectResolver;
    
    /**
     * 实例解析器
     * 
     * @var InstanceResolver
     */
    protected $instanceResolver;
    
    /**
     * 注入器实例
     * 
     * @var InjectionInterface
     */
    protected $injection;
    
    /**
     * 构造函数
     * 
     * @param ContainerInterface $container
     * @param InjectionInterface $injection
     */
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        
        $this->injection = $injection;
    }

    /**
     * 解析并返回解析后的值
     * 
     * @param DefinitionInterface $definition
     * @param array $parameters
     * @return mixed
     */
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        if ($definition instanceof SelfResolvingDefinition) {
            return $definition->resolve($this->container);
        }
        return $this->getDefinitionResolver($definition)->resolve($definition, $parameters);
    }

    /**
     * 是否可解析
     * @param DefinitionInterface $definition
     * @param array $parameters
     * @return bool
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool
    {
        if ($definition instanceof SelfResolvingDefinition) {
            return $definition->isResolvable($this->container);
        }
        
        return $this->getDefinitionResolver($definition)->isResolvable($definition, $parameters);
    }

    /**
     * 根据定义类型获取一个默认的解析器
     * 
     * @param DefinitionInterface $definition 定义实例
     * @throws \RuntimeException 找不到合适的解析器时抛出
     * 
     * @return DefinitionResolverInterface 返回的解析类实例
     */
    private function getDefinitionResolver(DefinitionInterface $definition): DefinitionResolverInterface
    {
        switch (true) {
            case $definition instanceof CallableDefinition:
                if (! $this->callableResolver) {
                    $this->callableResolver = new CallableResolver($this->container, $this);
                }
                return $this->callableResolver;
            case $definition instanceof InstanceDefinition:
                if (!$this->instanceResolver) {
                    $this->instanceResolver = new InstanceResolver($this->container, $this->injection);
                }
                return $this->instanceResolver;
            case $definition instanceof ObjectDefinition:
                if (! $this->objectResolver) {
                    $this->objectResolver = new ObjectResolver($this->container, $this->injection);
                }
                return $this->objectResolver;

            default:
                throw new \RuntimeException('No definition resolver was configured for definition of type ' . get_class($definition));
        }
    }
}

/**
* 回调解析类
* 
* @package Tiny.DI.Definition
* @since 2022年1月4日下午5:47:58
* @final 2022年1月4日下午5:47:58
*/
class CallableResolver implements DefinitionResolverInterface
{

    /**
     * 容器实例
     * 
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 构造函数
     * 
     * @param ContainerInterface $container 容器实例
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionResolverInterface::resolve()
     */
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        return $this->container->call($definition->getCallable());
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionResolverInterface::isResolvable()
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool
    {
        return is_callable($definition->getCallable());
    }
}

/**
* 实例定义解析器
* 
* @package Tiny.DI.Definition
* @since 2022年1月4日下午5:55:16
* @final 2022年1月4日下午5:55:16
*/
class ObjectResolver implements DefinitionResolverInterface
{
    /**
     * 容器实例
     * 
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 注入器实例
     * 
     * @var Injection
     */
    protected $injection;
    
    /**
     * 类的反射器实例
     * 
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        $this->injection = $injection;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionResolverInterface::resolve()
     */
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        return $this->createInstance($definition, $parameters);
    }

    /**
     * 根据定义创建实例 并自动注入
     * 
     * @param ObjectDefinition $definition
     * @param array $parameters
     * @throws InvalidDefinitionException
     * @return mixed 
     */
    protected function createInstance(ObjectDefinition $definition, array $parameters = [])
    {   
        $className = $definition->getClassName();
        
        if (!class_exists($className)) {
            throw new InvalidDefinitionException(sprintf('Entry "%s" cannot be resolved: the class doesn\'t exist', $definition->getName()));
        }
        $classReflection = new \ReflectionClass($className);
        if (!$classReflection->isInstantiable())
        {
            throw new InvalidDefinitionException(sprintf('Entry "%s" cannot be resolved: the class is not instantiable', $definition->getName()));
        }
        
        $args = [];
        $constructor = $classReflection->getConstructor();
        if ($constructor)
        {
            $args = $this->injection->getParameters($constructor,$parameters);
        }
        $object = new $className(...$args);
        
        // inject
        $this->injection->injectObject($classReflection, $object);
        return $object;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionResolverInterface::isResolvable()
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool
    {
        $className = $definition->getClassName();
        if (!class_exists($className)) {
           return false;
        }
        
        $classReflection = new \ReflectionClass($className);
        if (!$classReflection->isInstantiable())
        {
            return false;    
        }
        return true;
    }
}

/**
* 实例解析类
* 
* @package Tiny.DI.Definition
* @since 2022年1月4日下午11:31:34
* @final 2022年1月4日下午11:31:34
*/
class InstanceResolver extends ObjectResolver
{
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionResolverInterface::resolve()
     */
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        $instance = $definition->getInstance();
        $classReflection = new \ReflectionClass($definition->getClassName());
        $this->injection->injectObject($classReflection, $instance);
        return $instance;
    }
}
?>