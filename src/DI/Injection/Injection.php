<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Autowiring.php
 * @author King
 * @version stable 1.0
 * @Date 2022年1月1日下午6:23:09
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2022年1月1日下午6:23:09 第一次建立该文件
 *          King 2022年1月1日下午6:23:09 修改
 *         
 */
namespace Tiny\DI\Injection;

use Tiny\DI\ContainerInterface;

/**
* 注入器接口
* 
* @package Tiny.DI.Injection
* @since 2022年1月4日下午2:59:53
* @final 2022年1月4日下午2:59:53
*/
interface InjectionInterface
{
    /**
     * 注入属性成员
     * 
     * @param \ReflectionClass $reflectionClassInstance
     * @param object $classInstance
     */
    public function injectObject(\ReflectionClass $classReflection, $object);
    
    /**
     * 获取注入的函数参数
     * 
     * @param \ReflectionFunctionAbstract $reflection
     * @param array $resolvedParameters
     * @return array
     */
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $resolvedParameters = []): array;
}


/**
* 注入器
* 
* @package Tiny.DI.Injection
* @since 2022年1月4日下午4:29:49
* @final 2022年1月4日下午4:29:49
*/
class Injection implements InjectionInterface
{

    /**
     * 注入的容器
     * 
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 参数注入器
     * 
     * @var ParameterInjection
     */
    protected $parameterInjection;

    /**
     *  实例注入器
     *  
     * @var ObjectInjection
     */
    protected $objectInjection;

    /**
     *  构造函数
     *  
     * @param ContainerInterface $container 注入取值的容器
     * @param bool $isAnnotationAutowiring 是否通过注解注入函数和构造函数
     */
    public function __construct(ContainerInterface $container, bool $isAnnotationAutowiring = true)
    {
        $this->container = $container;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Injection\InjectionInterface::getParameters()
     */
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $resolvedParameters = []): array
    {
        return $this->getParameterInjection()->getParameters($reflection, $resolvedParameters);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Injection\InjectionInterface::injectProperties()
     */
    public function injectObject(\ReflectionClass $classReflection, $object)
    {
        return $this->getObjectInjection()->injectObject($classReflection, $object);
    }
    
    /**
     * 获取参数注入器
     * 
     * @return \Tiny\DI\Injection\ParameterInjection
     */
    protected function getParameterInjection()
    {
        if (!$this->parameterInjection)
        {
            $this->parameterInjection = new ParameterInjection($this->container);
        }
        return $this->parameterInjection;
    }
    
    /**
     * 获取属性注入器
     * 
     * @return \Tiny\DI\Injection\ObjectInjection
     */
    protected function getObjectInjection()
    {
        if (!$this->objectInjection)
        {
            $this->objectInjection = new ObjectInjection($this->container, $this);
        }
        return $this->objectInjection;
    }
}

/**
* 属性注入器
* 
* @package namespace
* @since 2022年1月4日下午3:12:44
* @final 2022年1月4日下午3:12:44
*/
class ObjectInjection
{
    /**
     * 提供取值的容器实例
     * 
     * @var ContainerInterface
     */
    private $container;

    /**
     *  
     * @var InjectionInterface
     */
    private $injection;
    
    /**
     * 构造函数
     * 
     * @param ContainerInterface $container 提供取值的容器实例
     */
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        $this->injection = $injection;
    }

    public function injectObject(\ReflectionClass $classReflection, object $object)
    {
        $this->injectProperties($classReflection, $object);
        $this->injectMethods($classReflection, $object);
    }
    /**
     * 注入属性成员
     * 
     * @param \ReflectionClass $reflectionClassInstance
     * @param object $classInstance
     */
    public function injectProperties(\ReflectionClass $classReflection, object $object)
    {
        $properties = $classReflection->getProperties();
        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $this->readProperty($property, $object);
        }

        $class = $classReflection;
        while ($class = $class->getParentClass()) {

            foreach ($class->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
                if ($property->isStatic()) {
                    continue;
                }
                $this->readProperty($property, $object);
            }
        }
    }

    public function injectMethods(\ReflectionClass $classReflection, object $object)
    {
        $methods = $classReflection->getMethods();
        foreach($methods as $method)
        {
            $this->injectMethod($method, $object);
        }
    }
    
    protected function injectMethod(\ReflectionMethod $method, object $object)
    {
        if($method->isConstructor() || $method->isDestructor() || $method->isAbstract())
        {
            return;
        }
        $methodComment = $method->getDocComment();
        if (!$this->isAutowired($methodComment))
        {
            return;
        }
        $args = $this->injection->getParameters($method);
        if ($method->isPrivate() || $method->isProtected())
        {
            $method->setAccessible(true);
        }
        $method->invokeArgs($object, $args);
    }
    /**
     * 读取成员属性的值
     * 
     * @param \ReflectionProperty $property
     * @param object $object
     */
    protected function readProperty(\ReflectionProperty $property, object $object)
    {
        $propertyComment = $property->getDocComment();
        if (! $propertyComment || ! $this->isAutowired($propertyComment)) {
            return;
        }
        $propertyValue = $this->readPropertyFromPHPType($property);
        if (! $propertyValue) {
            $propertyValue = $this->readPropertyFromAnnotation($property, $propertyComment);
        }
        if (! $propertyValue) {
            return;
        }
        if (! $property->isPublic()) {
            $property->setAccessible(true);
        }
        $property->setValue($object, $propertyValue);
    }
    
    /**
     * 通过注解读取成员属性的值
     * 
     * @param \ReflectionProperty $property
     * @param string $propertyComment
     * @return void|\Tiny\DI\ContainerInterface|mixed
     */
    protected function readPropertyFromAnnotation(\ReflectionProperty $property, string $propertyComment)
    {
        $namespace = $property->getDeclaringClass()->getNamespaceName();
        if (! preg_match('/\s*\*\s+@var\s+([\\\\\w]+)/i', $propertyComment, $out)) {
            return;
        }
        $propertyClass = $out[1];
        if (strpos($propertyClass, "\\") === false) {
            $propertyClass = $namespace . '\\' . $propertyClass;
        } elseif ($propertyClass[0] == '\\') {

            $propertyClass = substr($propertyClass, 1);
        }
   
        return $this->readPropertyFromContainer($propertyClass);
    }

    /**
     * 从PHP类型读取
     * @param \ReflectionProperty $property
     * @return boolean|\Tiny\DI\ContainerInterface|mixed
     */
    protected function readPropertyFromPHPType(\ReflectionProperty $property)
    {
        $propertyType = $property->getType();
        if (! $propertyType) {
            return false;
        }
        if (! $propertyType instanceof \ReflectionNamedType) {
            // Union types are not supported
            return false;
        }
        if ($propertyType->isBuiltin()) {
            // Primitive types are not supported
            return false;
        }

        $propertyClass = $propertyType->getName();
        return $this->readPropertyFromContainer($propertyClass);
    }
    
    /**
     * 从容器中读取值
     * 
     * @param string $name 类名
     * 
     * @return \Tiny\DI\ContainerInterface|mixed
     */
    protected function readPropertyFromContainer($name)
    {
        if ($name === ContainerInterface::class) {
            return $this->container;
        } elseif ($this->container->has($name)) {
            return $this->container->get($name);
        }
    }

    /**
     * 是否自动注解
     * 
     * @param string $comment
     * @return boolean
     */
    protected function isAutowired(string $comment)
    {
        return stripos($comment, '@autowired') !== false;
    }
}

/**
* 函数的参数注入器
* @package namespace
* @since 2022年1月4日下午4:24:52
* @final 2022年1月4日下午4:24:52
*/
class ParameterInjection
{

    /**
     * 提供注入的容器实例
     * 
     * @var ContainerInterface
     */
    private $container;

    /**
     * 构造函数
     * 
     * @param ContainerInterface $container  提供注入的容器实例
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 获取函数反射实例的参数解析
     *  
     * @param \ReflectionFunctionAbstract $reflection
     * @param array $providedParameters
     * @param array $resolvedParameters
     * @return array
     */
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $resolvedParameters = []): array
    {
        // $providedParameters = $this->getProvidedParameters($reflection);
        $reflectionParameters = $reflection->getParameters();

        // custom class
        $this->getTypeParameters($reflectionParameters, $resolvedParameters);

        // default
        $this->getDefaultParameters($reflectionParameters, $resolvedParameters);

        // Sort by array key because call_user_func_array ignores numeric keys
        ksort($resolvedParameters);

        // Check all parameters are resolved
        $diff = array_diff_key($reflectionParameters, $resolvedParameters);
        $parameter = reset($diff);
        if ($parameter && \assert($parameter instanceof \ReflectionParameter) && ! $parameter->isVariadic()) {
            throw new NotEnoughParametersException(sprintf('Unable to invoke the callable because no value was given for parameter %d ($%s)', $parameter->getPosition() + 1, $parameter->name));
        }
        return $resolvedParameters;
    }

    /**
     *  获取带有类型标识的实例
     *  
     * @param array $parameters 反射参数实例集合
     * @param array $resolvedParameters 已经解析的数组
     * @return \Tiny\DI\ContainerInterface|mixed
     */
    protected function getTypeParameters(array $parameters, array &$resolvedParameters)
    {
        if (! empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }
        foreach ($parameters as $index => $parameter) {
            $parameterType = $parameter->getType();
            if (! $parameterType) {
                continue;
            }
            if (! $parameterType instanceof \ReflectionNamedType) {
                // Union types are not supported
                continue;
            }
            if ($parameterType->isBuiltin()) {
                // Primitive types are not supported
                continue;
            }
            $parameterClass = $parameterType->getName();

            if ($parameterClass === ContainerInterface::class) {
                $resolvedParameters[$index] = $this->container;
            } elseif ($this->container->has($parameterClass)) {
                $resolvedParameters[$index] = $this->container->get($parameterClass);
            }
        }

        return $resolvedParameters;
    }

    /**
     * 获取默认参数
     * 
     * @param array $parameters 参数反射实例集合
     * @param array $resolvedParameters 已经解析的数组
     */
    protected function getDefaultParameters(array $parameters, array &$resolvedParameters)
    {
        if (! empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            \assert($parameter instanceof \ReflectionParameter);
            if ($parameter->isDefaultValueAvailable()) {
                try {
                    $resolvedParameters[$index] = $parameter->getDefaultValue();
                } catch (\ReflectionException $e) {
                    // Can't get default values from PHP internal classes and functions
                }
            } else {
                $parameterType = $parameter->getType();
                if ($parameterType && $parameterType->allowsNull()) {
                    $resolvedParameters[$index] = null;
                }
            }
        }
    }
}

?>