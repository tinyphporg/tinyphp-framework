<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name InstanceDefinition.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:03:59
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:03:59 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition;

/**
 *  实例定义类
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午11:29:58
 * @final 2022年1月4日下午11:29:58
 */
class InstanceDefinition extends ObjectDefinition
{
    /**
     *  获取实例
     *
     * @var mixed
     */
    private $instance;
    
    /**
     * 构造函数
     * 
     * @param string $name
     * @param object $instance
     */
    public function __construct($name, $instance)
    {
        $className = get_class($instance);
        $this->instance = $instance;
        parent::__construct($name, $className);
    }
    
    /**
     * 获取实例
     * 
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }
}
?>