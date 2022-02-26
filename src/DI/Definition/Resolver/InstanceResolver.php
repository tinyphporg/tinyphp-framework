<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name InstanceResolver.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:11:02
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:11:02 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition\Resolver;


use Tiny\DI\Definition\DefinitionInterface;

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
     * @see \Tiny\DI\Definition\Resolver\ObjectResolver::resolve()
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