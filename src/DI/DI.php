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
    protected $definitionResolver;

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
    public function __construct(DefinitionProviderInterface $defintionProvider = null, ContainerInterface $wrapperContainer = null, InvokerInterface $invokerFactory = null)
    {
        $this->defintionProvider = $defintionProvider ?: $this->createDefineProvider();
        $this->delegateContainer = $wrapperContainer ?: $this;
        $this->invoker = $invokerFactory ?: $this;
        $this->definitionResolver = new ResolverDispatcher($this->delegateContainer);
        $this->resolvedEntries = [
            self::class => $this,
            DefinitionProviderInterface::class => $defintionProvider,
            get_class($defintionProvider) => $defintionProvider,
            Container::class => $this,
            ContainerInterface::class => $this,
            FactoryInterface::class => $this,
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

    protected function getInvoker()
    {
        if (! $this->invoker)
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
        // $provider->setMutablDefintionProivder();
        return $provider;
    }

    /**
     *
     * @param string $name
     *
     * @return Defintion|null
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

    protected function createDefineProvider()
    {
        return new DefintionProivder();
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

interface DefinitionProviderInterface
{

    public function getDefinition(string $name);
    
    public function getDefinitions(): array;
}

interface DefinitionInterface
{

    public function getName(): string;

    public function setName(string $name);
}

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

class DefintionProivder implements DefinitionProviderInterface
{

    /**
     *
     * @var array
     */
    protected $definitionProivders = [];

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

class ResolverDispatcher
{

    protected $container;

    protected $callableResolver;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            // return $definition->isResolvable($this->container);
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

    public function __construct(ContainerInterface $container, ResolverDispatcher $resolver)
    {
        $this->container = $container;
    }

    public function resolve(DefinitionInterface $definition)
    {
        if (! $this->invoker)
        {
             $this->invoker = new Invoker(null, $this->container);
        }

        return $this->invoker->call($definition->getCallable());
    }
    
    public function isResolvable(DefinitionInterface $definition):bool
    {
        return true;
    }
}

class Invoker
{

    private $parameterResolver;

    private $container;

    public function __construct(?ParameterResolverInterface $parameterResolver = null, ?ContainerInterface $container = null)
    {
        $this->parameterResolver = $parameterResolver ?: new ParameterResolver($container);
        $this->container = $container;
    }

    public function call($callable, array $parameters = [])
    {
        if ($this->container)
        {
            $callable = $this->resolveCallable($callable);
        }

        $callableReflection = $this->createCallableReflection($callable);
        
        $args = $this->parameterResolver->getParameters($callableReflection, $parameters, []);

        // Sort by array key because call_user_func_array ignores numeric keys
        ksort($args);

        // Check all parameters are resolved
        $diff = array_diff_key($callableReflection->getParameters(), $args);
        $parameter = reset($diff);
        if ($parameter && \assert($parameter instanceof \ReflectionParameter) && ! $parameter->isVariadic())
        {
            throw new NotEnoughParametersException(sprintf('Unable to invoke the callable because no value was given for parameter %d ($%s)', $parameter->getPosition() + 1, $parameter->name));
        }

        return call_user_func_array($callable, $args);
    }

    /**
     *
     * @return ParameterResolver By default it's a ResolverChain
     */
    public function getParameterResolver(): ParameterResolver
    {
        return $this->parameterResolver;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
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
            catch (NotFoundExceptionInterface $e)
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
            catch (NotFoundExceptionInterface $e)
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

interface ParameterResolverInterface
{
}

class ParameterResolver implements ParameterResolverInterface
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getParameters(\ReflectionFunctionAbstract $reflection, array $providedParameters, array $resolvedParameters): array
    {
        $reflectionParameters = $reflection->getParameters();
        
        foreach ($this->resolvers as $resolver)
        {
            $resolvedParameters = $resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

            $diff = array_diff_key($reflectionParameters, $resolvedParameters);
            if (empty($diff))
            {
                // Stop traversing: all parameters are resolved
                return $resolvedParameters;
            }
        }

        return $resolvedParameters;
    }

    public function get()
    {
    }
}
?>