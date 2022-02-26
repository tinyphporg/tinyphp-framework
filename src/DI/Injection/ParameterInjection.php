<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ParameterInjection.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:48:10
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:48:10 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Injection;

use Tiny\DI\ContainerInterface;

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
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $proivderParameters = []): array
    {
        // $providedParameters = $this->getProvidedParameters($reflection);
        $reflectionParameters = $reflection->getParameters();
        
        // custom class
        $resolvedParameters = [];
        
        $this->getTypeParameters($reflectionParameters, $resolvedParameters, $proivderParameters);
        
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
    protected function getTypeParameters(array $parameters, array &$resolvedParameters, array $proivderParameters)
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
            
            // builtin type
            if ($parameterType->isBuiltin()) {
                $parameterName = $parameter->getName();
                if (key_exists($parameterName, $proivderParameters))
                {
                    
                    $resolvedParameters[$index] = $proivderParameters[$parameterName];
                }
                continue;
            }
            
            // class type
            $parameterClass = $parameterType->getName();
            if ($parameterClass === ContainerInterface::class) {
                $resolvedParameters[$index] = $this->container;
            } elseif (key_exists($parameterClass, $proivderParameters))
            {
                $resolvedParameters[$index] = $proivderParameters[$parameterClass];
            }
            elseif ($this->container->has($parameterClass)) {
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