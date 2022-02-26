<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name MvcEvent.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:28:21
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:28:21 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event;

use Tiny\Event\Event;
use Tiny\MVC\Event\Listener\BootstrapEventListener;
use Tiny\MVC\Event\Listener\RequestEventListener;
use Tiny\MVC\Event\Listener\RouteEventListener;
use Tiny\MVC\Event\Listener\DispatchEventListener;
/**
 * MVC事件实例
 *
 * @package Tiny.MVC
 * @since 2022年1月15日上午8:58:45
 * @final 2022年1月15日上午8:58:45
 */
class MvcEvent extends Event
{
    
    /**
     * 引导事件
     *
     * @var string
     */
    const EVENT_BOOTSTRAP = BootstrapEventListener::class;
    
    /**
     * 路由初始化事件
     *
     * @var string
     */
    const EVENT_ROUTER_STARTUP = RouteEventListener::class . '.onRouterStartup';
    
    /**
     * 路由结束事件
     *
     * @var string
     */
    const EVENT_ROUTER_SHUTDOWN = RouteEventListener::class . '.onRouterShutdown';
    
    /**
     * 派发前事件
     *
     * @var string
     */
    const EVENT_PRE_DISPATCH = DispatchEventListener::class . '.onPreDispatch';
    
    /**
     * 派发后事件
     *
     * @var string
     */
    const EVENT_POST_DISPATCH = DispatchEventListener::class . '.onPostDispatch';
    
    /**
     * 请求开始事件
     *
     * @var string
     */
    const EVENT_BEGIN_REQUEST = RequestEventListener::class . '.onBeginRequest';
    
    /**
     * 请求结束事件
     *
     * @var string
     */
    const EVENT_END_REQUEST = RequestEventListener::class . '.onEndRequest';
}
?>