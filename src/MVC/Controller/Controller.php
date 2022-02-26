<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Controller.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月10日下午11:09:50
 * @Class List
 * @Function List
 * @History King 2017年3月10日下午11:09:50 0 第一次建立该文件
 *          King 2017年3月10日下午11:09:50 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Controller;

use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Response\WebResponse;
use Tiny\MVC\Application\WebApplication;


/**
 * WEB控制器
 *
 * @package Tiny.Application.Controller
 * @since 2017年3月11日上午12:20:13
 * @final 2017年3月11日上午12:20:13
 */
abstract class Controller extends ControllerBase
{   
    /**
     * 当前应用程序实例
     *
     * @var WebApplication
     */
    protected $application;
    
    /**
     * 当前请求参数
     *
     * @var WebRequest
     */
    protected $request;
    
    /**
     * 当前响应实例
     *
     * @var WebResponse
     */
    protected $response;
}
?>