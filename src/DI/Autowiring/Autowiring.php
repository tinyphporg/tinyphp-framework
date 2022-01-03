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
namespace Tiny\DI\Autowiring;

use Tiny\DI\ContainerInterface;

interface  AutowiringInterface
{
    
}

interface ParameterResolverInterface
{
    
}


class Autowiring implements AutowiringInterface
{
    protected ContainerInterface $container;
    
    protected ParameterResolver $parameterResolver;
    
    protected AnnotationResolver $annotationResolver;
    
    public function __construct(ContainerInterface $container, bool $isAnnotationAutowiring = true)
    {
        $this->parameterResolver = new ParameterResolver($container);
        $this->annotationResolver = new AnnotationResolver($container);
    }
    
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $resolvedParameters = []): array
    {
        //$resolvedParameters += $this->annotationResolver->getParameters($reflection, $resolvedParameters);
        return $this->parameterResolver->getParameters($reflection, $resolvedParameters);
    }
    
    public function antowireProperties(\ReflectionClass $reflectionClassInstance, $classInstance)
    {
        return $this->annotationResolver->antowireProperties($reflectionClassInstance, $classInstance);
    }
    
    protected function getParameterResolver()
    {
        if (!$this->parameterResolver)
        {
        }
    }
    
}

class AnnotationResolver
{
    private $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function antowireProperties(\ReflectionClass $reflectionClassInstance, $classInstance)
    {
        $properties = $reflectionClassInstance->getProperties();
        foreach($properties as $property)
        {
            if ($property->isStatic())
            {
                continue;
            }
            $this->readProperty($property,$classInstance);
        }
        
        

        $class = $reflectionClassInstance;
        while ($class = $class->getParentClass()) {
            
            foreach ($class->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
                if ($property->isStatic()) {
                    continue;
                }
                $this->readProperty($property, $classInstance);
            }
        }
    }
    
    protected function readProperty(\ReflectionProperty $property, $object)
    {   
        
        $propertyComment = $property->getDocComment();
        if (!$propertyComment || !$this->isAutowired($propertyComment))
        {
            return;
        }
        $propertyValue = $this->readPropertyFromContainer($property);
        if (!$propertyValue)
        {
            $this->readPropertyFromAnnotation($property, $propertyComment);
        }
        if (!$propertyValue)
        {
            return;
        }
        if (! $property->isPublic()) 
        {
            $property->setAccessible(true);
        }
        $property->setValue($object, $propertyValue);
    }
    
    protected function readPropertyFromAnnotation(\ReflectionProperty $property, string $propertyComment)
    {
        $namespace =  $property->getDeclaringClass()->getNamespaceName();
        if (!preg_match('/\s*\*\s+@var\s+([\\\\\w]+)/i', $propertyComment, $out))
        {
            return;
        }
        $propertyClass = $out[1];
        if (strpos($propertyClass, "\\") === false)
        {
            $propertyClass = $namespace . '\\' . $propertyClass;
        }
        elseif ($propertyClass[0] == '\\')
        {

                $propertyClass = substr($propertyClass, 1);
            
        }
        
        if ($propertyClass === ContainerInterface::class) {
            return $this->container;
        } elseif ($this->container->has($propertyClass)) {
            return $this->container->get($propertyClass);
        }
        
    }
    protected function readPropertyFromContainer(\ReflectionProperty $property)
    {
        $propertyType = $property->getType();
        if (!$propertyType) {
            return false;
        }
        if (!$propertyType instanceof \ReflectionNamedType) {
            // Union types are not supported
            return false;
        }
        if ($propertyType->isBuiltin()) {
            // Primitive types are not supported
            return false;
        }
        
        $propertyClass = $propertyType->getName();
        
        if ($propertyClass === ContainerInterface::class) {
            return $this->container;
        } elseif ($this->container->has($propertyClass)) {
            return $this->container->get($propertyClass);
        }
    }
    
    protected function isAutowired(string $comment)
    {
        return stripos($comment, '@autowired') !== false;
    }
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $resolvedParameters = []): array
    {
        $args = $this->resolvFunctionReflection($reflection);
    }
    
    protected function resolvFunctionReflection(\ReflectionFunctionAbstract $reflection)
    {
        $comment = $reflection->getDocComment();
        echo $comment;
        if (!$comment)
        {
            return [];
        }
        if (!preg_match("/^.*(@inject)(?:\((\w+)=(\"(.+)\")(,(\"(.+)\"))*\))?.*?$/is", $comment, $out))
        {
            return [];
        }
        print_r($out);
    }
    
}

class ParameterResolver implements ParameterResolverInterface
{
    
    private $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    
    /**
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
        if ($parameter && \assert($parameter instanceof \ReflectionParameter) && ! $parameter->isVariadic())
        {
            throw new NotEnoughParametersException(sprintf('Unable to invoke the callable because no value was given for parameter %d ($%s)', $parameter->getPosition() + 1, $parameter->name));
        }
        
        return $resolvedParameters;
    }
    
    
    protected function getTypeParameters(array $parameters, array &$resolvedParameters)
    {
        if (!empty($resolvedParameters))
        {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }
        foreach($parameters as $index => $parameter)
        {
            $parameterType = $parameter->getType();
            if (!$parameterType) {
                continue;
            }
            if (!$parameterType instanceof \ReflectionNamedType) {
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
    
    protected function getDefaultParameters(array $parameters, array &$resolvedParameters)
    {
        if (!empty($resolvedParameters))
        {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }
        
        foreach($parameters as $index => $parameter)
        {
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