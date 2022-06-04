<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name DefinitionProviderInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午2:01:20
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午2:01:20 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition\Provider;


use Tiny\DI\Definition\DefinitionInterface;

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
     * @return DefinitionInterface|false
     */
    public function getDefinition(string $name);
}
?>