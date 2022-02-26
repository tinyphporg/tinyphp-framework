<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name DefinitionResolverInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:05:15
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:05:15 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition\Resolver;

use Tiny\DI\Definition\DefinitionInterface;

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
?>