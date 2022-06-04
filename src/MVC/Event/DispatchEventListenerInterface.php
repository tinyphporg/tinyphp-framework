<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name DispatchEventListenerInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:43:09
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:43:09 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event;

use Tiny\Event\EventListenerInterface;

/**
 * 派发事件监听接口
 *
 * @package Tiny.MVC.Application
 * @since 2022年1月15日上午9:03:46
 * @final 2022年1月15日上午9:03:46
 */
interface DispatchEventListenerInterface extends EventListenerInterface
{
    
    /**
     * 派发前事件
     *
     * @param MvcEvent $event
     * @param array $params
     */
    public function onPreDispatch(MvcEvent $event, array $params);
    
    /**
     * 派发后事件
     *
     * @param MvcEvent $event
     * @param array $params
     */
    public function onPostDispatch(MvcEvent $event, array $params);
}
?>