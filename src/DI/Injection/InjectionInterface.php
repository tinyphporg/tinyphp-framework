<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name InjectionInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:44:45
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:44:45 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Injection;

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
?>