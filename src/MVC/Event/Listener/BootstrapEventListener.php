<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name BootstrapEventListenerInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:39:55
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:39:55 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event\Listener;

use Tiny\MVC\Event\MvcEvent;
use Tiny\Event\EventListenerInterface;

/**
 * 引导类事件监听
 *
 * @package Tiny.MVC.Application
 * @since 2022年1月19日上午12:19:25
 * @final 2022年1月19日上午12:19:25
 */
interface BootstrapEventListener extends EventListenerInterface
{
    /**
     * 引导事件
     *
     * @param MvcEvent $event
     * @param array $params
     */
    public function onBootstrap(MvcEvent $event, array $params);
}
?>