<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Iplugin.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月12日下午2:04:02
 * @Class List
 * @Function List
 * @History King 2017年3月12日下午2:04:02 0 第一次建立该文件
 *          King 2017年3月12日下午2:04:02 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\Plugin;

/**
 * 插件接口
 *
 * @package Tiny.Application.Plugin
 * @since 2017年3月12日下午1:47:01
 * @final 2017年3月12日下午1:47:01
 */
interface Iplugin
{

    /**
     * 本次请求初始化时发生的事件
     *
     * @return void
     */
    public function onBeginRequest();

    /**
     * 本次请求结束时发生的事件
     *
     * @return void
     */
    public function onEndRequest();

    /**
     * 执行路由前发生的事件
     *
     * @return void
     */
    public function onRouterStartup();

    /**
     * 执行路由后发生的事件
     *
     * @return void
     */
    public function onRouterShutdown();

    /**
     * 执行分发前发生的动作
     *
     * @return void
     */
    public function onPreDispatch();

    /**
     * 执行分发后发生的动作
     *
     * @return void
     */
    public function onPostDispatch();
}
?>