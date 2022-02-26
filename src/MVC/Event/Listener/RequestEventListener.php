<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name RequestEventListenerInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:44:31
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:44:31 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event\Listener;

use Tiny\Event\EventListenerInterface;
use Tiny\MVC\Event\MvcEvent;

/**
 *
 * @package
 * @since 2022年1月15日上午9:05:22
 * @final 2022年1月15日上午9:05:22
 */
interface RequestEventListener extends EventListenerInterface
{
    
    /**
     * 请求开始事件
     *
     * @param MvcEvent $event
     * @param array $params
     */
    public function onBeginRequest(MvcEvent $event, array $params);
    
    /**
     * 请求结束事件
     *
     * @param MvcEvent $event
     * @param array $params
     */
    public function onEndRequest(MvcEvent $event, array $params);
}
?>