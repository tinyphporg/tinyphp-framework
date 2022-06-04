<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name RouteEventListenerInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:41:16
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:41:16 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event;

use Tiny\Event\EventListenerInterface;

/**
 * 路由事件监听接口
 *
 * @package Tiny.MVC.Application
 * @since 2022年1月15日上午8:59:42
 * @final 2022年1月15日上午8:59:42
 */
interface RouteEventListenerInterface extends EventListenerInterface
{
    
    /**
     * 路由初始化
     *
     * @param MvcEvent $event
     * @param array $params
     */
    public function onRouterStartup(MvcEvent $event, array $params);
    
    /**
     * 路由技术
     *
     * @param MvcEvent $event
     * @param array $params
     */
    public function onRouterShutdown(MvcEvent $event, array $params);
}
?>