<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name WebApplication.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:50:39
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:50:39 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Application;

use Tiny\MVC\Web\HttpCookie;
use Tiny\MVC\Web\HttpSession;
use Tiny\Event\Event;
use Tiny\Runtime\ExceptionHandler;

/**
 * 命令行应用实例
 *
 * @package Tiny.Application
 * @since 2017年4月5日下午11:31:23
 * @final 2017年4月5日下午11:31:23
 */
class WebApplication extends ApplicationBase
{   
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Application\ApplicationBase::onException()
     */
    public function onException(Event $event, \Throwable $exception, ExceptionHandler $handler)
    {
        // 输出404
        $code = $exception->getCode();
        if ($this->response) {
            if ($code === E_NOFOUND) {
              $this->response->setStatusCode(E_NOFOUND);
            }
        }
        parent::onException($event, $exception, $handler);
    }
}
?>