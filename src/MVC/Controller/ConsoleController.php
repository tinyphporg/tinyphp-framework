<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ConsoleController.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月11日下午11:54:19
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月11日下午11:54:19 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Controller;

use Tiny\MVC\Request\ConsoleRequest;
use Tiny\MVC\Response\ConsoleResponse;
use Tiny\MVC\Application\ConsoleApplication;

/**
 * 命令行控制器基类
 *
 * @package Tiny.Application.Controller;
 * @since 2017年3月12日下午3:04:19
 * @final 2017年3月12日下午3:04:19
 */
abstract class ConsoleController extends ControllerBase
{
    /**
     * 当前应用程序实例
     *
     * @var ConsoleApplication
     */
    protected $application;
    
    /**
     * 当前请求参数
     *
     * @var ConsoleRequest
     */
    protected $request;
    
    /**
     * 当前响应实例
     *
     * @var ConsoleResponse
     */
    protected $response;

    /**
     * 当控制器作为worker使用时的启动事件
     *  @return false 会退出守护进程程序
     */
    public function onstart()
    {
    }
    
    /**
     * 当控制器作为worker使用的停止事件
     */
    public function onstop()
    {
    }
}
?>