<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ExceptionEventListener.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:05:44
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:05:44 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Event;

/**
 * 事件监听句柄接口
 *
 * @package Tiny.Event
 * @since 2022年1月11日下午10:26:25
 * @final 2022年1月11日下午10:26:25
 */
interface ExceptionEventListener extends EventListenerInterface
{
    
    /**
     * 异常处理事件
     *
     * @param Event $event
     * @param array $params
     */
    public function onException(array $exception, array $exceptions);
}
?>