<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name SelfResolvingDefinition.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午2:00:05
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午2:00:05 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition;

use Tiny\DI\ContainerInterface;

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
?>