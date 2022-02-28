<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ControllerBase.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:22:35
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:22:35 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Controller;

use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\Response\Response;
use Tiny\MVC\Request\Request;

/**
 * 控制器积类
 *
 * @package Tiny.Application.Controller
 * @since 2017年3月12日下午2:57:20
 * @final 2017年3月12日下午2:57:20
 */
abstract class ControllerBase
{
    
    /**
     * 当前应用程序实例
     *
     * @var ApplicationBase
     */
    protected $application;
    
    /**
     * 当前请求参数
     *
     * @var Request
     */
    protected $request;
    
    /**
     * 当前响应实例
     *
     * @var Response
     */
    protected $response;
    
    /**
     * 通过应用实例初始化
     *
     * @param ApplicationBase $app
     */
    public function setApplication(ApplicationBase $app)
    {
        $this->application = $app;
        $this->request = $app->request;
        $this->response = $app->response;
    }
    
    /**
     * 关闭或开启调试模块
     *
     * @param bool $isDebug 是否输出调试模块
     * @return void
     */
    public function setDebug(bool $isDebug)
    {
        $this->application->isDebug = $isDebug;
    }
    
    /**
     * 写入日志
     *
     * @param string $id 日志ID
     * @param string $message 日志信息
     * @param int $priority 日志优先级别 0-7
     * @param array $extra 附加信息
     * @return void
     */
    public function log($id, $message, $priority = 1, $extra = [])
    {
        return $this->application->getLogger()->log($id, $message, $priority, $extra);
    }
    
    /**
     * 执行动作前触发
     *
     * @return void
     */
    public function onBeginExecute()
    {
    }
    
    /**
     * 结束后触发该事件
     *
     * @return void
     */
    public function onEndExecute()
    {
    }
    
    /**
     * 给试图设置预定义变量
     *
     * @param string|array $key 变量键 $key为array时 $value默认为空
     * @param mixed $value 变量值
     * @return bool
     */
    public function assign($key, $value = null)
    {
        return $this->application->getView()->assign($key, $value);
    }
    
    /**
     * 解析视图模板，注入到响应实例里
     *
     * @param string $viewPath 视图模板文件的相对路径
     *        视图相对路径
     * @return void
     */
    public function parse($viewPath)
    {
        return $this->application->getView()->display($viewPath);
    }
    
    /**
     * 解析视图模板并注入response
     *
     * @param string $viewPath
     * @return void
     */
    public function display($viewPath)
    {
        return $this->application->getView()->display($viewPath);
    }
    
    /**
     * 解析视图模板，并返回解析后的字符串
     *
     * @param string $viewPath 视图模板文件的相对路径
     * @return void
     */
    public function fetch($viewPath)
    {
        return $this->application->getView()->fetch($viewPath);
    }
    
    /**
     * 调用另外一个控制器的动作并派发
     *
     * @param string $cName 控制器名称
     * @param string $aName 动作名称
     * @return void
     */
    public function dispathch($cName, $aName)
    {
        return $this->application->dispatch($cName, $aName);
    }
    
    /**
     * 输出格式化的JSON串
     *
     * @param array ...$params 输入参数
     */
    public function outFormatJSON(...$params)
    {
        return $this->response->outFormatJSON(...$params);
    }
}
?>