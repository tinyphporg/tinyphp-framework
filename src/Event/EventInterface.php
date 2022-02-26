<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name EventInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:07:10
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:07:10 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Event;

/**
 * 事件接口
 *
 * @package Tiny.Event
 * @since 2022年1月11日下午11:32:18
 * @final 2022年1月11日下午11:32:18
 */
interface EventInterface
{
    
    /**
     * 获取事件句柄名
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * 设置事件句柄名
     *
     * @param string $name
     */
    public function setName(string $name);
    
    /**
     * 获取事件参数
     *
     * @return array
     */
    public function getParams(): array;
    
    /**
     * 设置事件参数
     *
     * @param array $params
     */
    public function setParams(array $params);
    
    /**
     * 停止事件冒泡
     *
     * @param bool $flag
     */
    public function stopPropagation($flag = true);
    
    /**
     * 是否停止事件冒泡
     *
     * @return bool
     */
    public function propagationIsStopped();
}
?>