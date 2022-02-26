<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name DefinitionInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午1:58:50
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午1:58:50 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition;

/**
 * 定义接口
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午4:41:48
 * @final 2022年1月4日下午4:41:48
 */
interface DefinitionInterface
{
    
    /**
     * 获取定义的名称
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * 设置定义的名称
     *
     * @param string $name
     */
    public function setName(string $name);
}
?>