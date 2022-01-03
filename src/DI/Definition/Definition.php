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
use Tiny\DI\Autowiring\Autowiring;




interface DefinitionInterface
{
    
    public function getName(): string;
    
    public function setName(string $name);
}

interface SelfResolvingDefinition
{
    /**
     * Resolve the definition and return the resulting value.
     *
     * @return mixed
     */
    public function resolve(ContainerInterface $container);
    
    /**
     * Check if a definition can be resolved.
     */
    public function isResolvable(ContainerInterface $container) : bool;
}

interface DefinitionProviderInterface
{
    
    public function getDefinition(string $name);
    
    public function getDefinitions(): array;
}

class DefintionProivder implements DefinitionProviderInterface
{
    
    /**
     *
     * @var array
     */
    protected $definitionProivders = [];
    
    protected $definitionFiles = [];
    
    protected $definitions = [];
    
    public function __construct(array $definitionProivders)
    {
        $this->definitionProivders = $definitionProivders;
    }
    
    public function getDefinition(string $name)
    {
        if (key_exists($name, $this->definitions))
        {
            return $this->definitions[$name];
        }
        
        foreach ($this->definitionProivders as $proivder)
        {
            $definition = $proivder->getDefinition($name);
            if ($definition)
            {
                return $definition;
            }
        }
    }
    
    public function getDefinitions(): array
    {
        
    }
    
    public function addDefinition(DefinitionInterface $definition)
    {
        $name = $definition->getName();
        $this->definitions[$name] = $definition;
    }
    
    public function addDefinitionFromPath($path)
    {
        if (is_array($path))
        {
            foreach($path as $p)
            {
                $this->addDefinitionFromPath($p);
            }
            return;
        }
        if (is_dir($path))
        {
            $files = scandir($path);
            foreach($files as $file)
            {
                if ($file == '.' || $file == '..')
                {
                    continue;
                }
                $this->addDefinitionFromPath($path . '/' . $file);
            }
            $this->definitionFiles[] = $path;
            return;
        }
        if(is_file($path) && pathinfo($path,PATHINFO_EXTENSION) == 'php')
        {
            if (!in_array($path, $this->definitionFiles))
            {
                $definitions = require $path;
                if (!is_array($definitions))
                {
                    return;
                }
                $this->addDefinitionFromArray($definitions);
                $this->definitionFiles[] = $path;
            }
        }
    }
    
    public function addDefinitionFromArray(array $sourceDefinitions)
    {
        foreach($sourceDefinitions as $name => $sourceDefinition)
        {
            if (is_int($name))
            {
                $this->resolveSourceDefinitionItem($sourceDefinition);
                continue;
            }
            if(is_string($name))
            {
                $this->resloveSourceDefinition($name, $sourceDefinition);
            }
            
        }
    }
    
    public function resolveSourceDefinitionItem($sourceDefinition)
    {
        if ($sourceDefinition instanceof DefinitionInterface)
        {
            return $this->addDefinition($sourceDefinition);
        }
        //echo strpos($sourceDefinition, '\\'),$sourceDefinition;
        if (is_string($sourceDefinition) && false !== strpos($sourceDefinition, '\\'))
        {
            $objectDefinition = new ObjectDefinition($sourceDefinition, $sourceDefinition);
            return $this->addDefinition($objectDefinition);
        }
    }
    
    public function resloveSourceDefinition($name, $sourceDefinition)
    {
        if ($sourceDefinition instanceof DefinitionInterface)
        {
            $sourceDefinition->setName($name);
            return $this->addDefinition($sourceDefinition);
        }
        if ($sourceDefinition instanceof \Closure || is_callable($sourceDefinition))
        {
            
            $definition = new CallableDefinition($name, $sourceDefinition);
            $this->addDefinition($definition);
        }
    }
}

class CallableDefinition implements DefinitionInterface
{
    
    protected $callable;
    
    protected $name;
    
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->callable = $value;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name)
    {
        $this->name = $name;
    }
    
    public function getCallable()
    {
        return $this->callable;
    }
}

class ObjectDefinition implements DefinitionInterface
{
    protected $name;
    protected $className;
    
    /**
     *
     * @var \ReflectionClass
     */
    protected $reflectionClassInstance;
    
    public function __construct($name, $className)
    {
        $this->name = $name;
        $this->className = $className;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getClassName(): string
    {
        return $this->className;
    }
    
    public function classExists()
    {
        return class_exists($this->className);
    }
    
    public function setName(string $name)
    {
        $this->name = $name;
    }
    
    public function isInstantiable()
    {
        return $this->getReflectionClassInstance()->isInstantiable();
    }
    
    /**
     *
     * @throws NotFoundClassException
     * @return \ReflectionClass
     */
    public function getReflectionClassInstance()
    {
        if (!$this->classExists())
        {
            throw new NotFoundClassException(sprintf("class %s is not exists!", $this->className));
        }
        
        if (!$this->reflectionClassInstance)
        {
            $this->reflectionClassInstance = new \ReflectionClass($this->className);
        }
        return $this->reflectionClassInstance;
    }
    
    
}

// reslover

interface DefinitionResolverInterface
{
    /**
     * Resolve the definition and return the resulting value.
     *
     * @return mixed
     */
    public function resolve(DefinitionInterface $definition);
    
    /**
     * Check if a definition can be resolved.
     */
    public function isResolvable(DefinitionInterface $definition) : bool;
}

class ResolverDispatcher
{
    
    protected $container;
    
    protected $callableResolver;
    

    
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        
    }
    
    /**
     *
     * @return 
     */
    public function getAutowiring()
    {
        if (!$this->autowiring)
        {
            $this->autowiring = $this->container->getAutoWiring();
        }
        return $this->autowiring;
    }
    
    
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        // Special case, tested early for speed
        if ($definition instanceof SelfResolvingDefinition)
        {
            return $definition->resolve($this->container);
        }
        
        $definitionResolver = $this->getDefinitionResolver($definition);
        
        return $definitionResolver->resolve($definition, $parameters);
    }
    
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool
    {
        // Special case, tested early for speed
        if ($definition instanceof SelfResolvingDefinition)
        {
            return $definition->isResolvable($this->container);
        }
        
        $definitionResolver = $this->getDefinitionResolver($definition);
        
        return $definitionResolver->isResolvable($definition, $parameters);
    }
    
    /**
     * Returns a resolver capable of handling the given definition.
     *
     * @throws \RuntimeException No definition resolver was found for this type of definition.
     */
    private function getDefinitionResolver(DefinitionInterface $definition): DefinitionResolverInterface
    {
        switch (true)
        {
            case $definition instanceof CallableDefinition:
                if (! $this->callableResolver)
                {
                    $this->callableResolver = new CallableResolver($this->container, $this);
                }
                return $this->callableResolver;
            case $definition instanceof ObjectDefinition:
                if (!$this->objectResolver)
                {
                    $this->objectResolver = new ObjectResolver($this->container, $this);
                }
                return $this->objectResolver;
            default:
                throw new \RuntimeException('No definition resolver was configured for definition of type ' . get_class($definition));
        }
    }
}

class CallableResolver implements DefinitionResolverInterface
{
    
    protected $definition;
    
    protected $container;
    
    protected $invoker;
    

    
    /**
     *
     * @var ResolverDispatcher
     */
    protected $definitionResolver;
    
    public function __construct(ContainerInterface $container, ResolverDispatcher $resolver)
    {
        $this->definitionResolver = $resolver;
        $this->container = $container;
    }
    
    public function resolve(DefinitionInterface $definition)
    {
        return $this->container->call($definition->getCallable());
    }
    
    public function isResolvable(DefinitionInterface $definition):bool
    {
        return true;
    }
}

class ObjectResolver implements DefinitionResolverInterface
{
    protected $definition;
    
    protected $container;
    
    protected $invoker;
    
    protected $definitionResolver;
    
    /**
     *
     * @var Autowiring
     */
    protected $autowiring;
    
    public function __construct(ContainerInterface $container, ResolverDispatcher $resolver)
    {
        $this->container = $container;
        $this->definitionResolver = $resolver;
    }
    
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        return $this->createInstance($definition, $parameters);
        
        
    }
    
    // @inject('name=eonfig.s', 'name=aaa', 'aaa')
    protected function createInstance(ObjectDefinition $definition, array $parameters = [])
    {
        
        if (!$definition->isInstantiable())
        {
            throw new InvalidDefinitionException(sprintf( 'Entry "%s" cannot be resolved: the class doesn\'t exist', $definition->getName()));
        }
        $className = $definition->getClassName();
        $reflectionClassInstance = $definition->getReflectionClassInstance();
        
        $construection = $reflectionClassInstance->getConstructor();
        
        $antowiring = $this->container->getAntowiring();
        $args = $antowiring->getParameters($construection, $parameters);
        // Sort by array key because call_user_func_array ignores numeric keys
        
        $classInstance = new $className(...$args);
        
        
        $antowiring->antowireProperties($reflectionClassInstance, $classInstance);
        
        return $classInstance;
        //$args = $this->resolveParameters($construection)    ;
    }
    
    
    protected function resolveParameters(\ReflectionMethod $reflectionMethod)
    {
        
    }
    
    //@inject('config.aasss');
    
    public function isResolvable(DefinitionInterface $definition):bool
    {
        return true;
    }
}
?>