<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ConsoleApplication.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月5日下午11:30:52
 * @Class List
 * @Function List
 * @History King 2017年4月5日下午11:30:52 0 第一次建立该文件
 *          King 2017年4月5日下午11:30:52 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Application;

use Tiny\Log\Logger;
use Tiny\Console\Worker\WorkerHandlerInterface;
use Tiny\Console\DaemonHandlerInterface;

/**
 * 命令行应用实例
 *
 * @package Tiny.Application
 * @since 2017年4月5日下午11:31:23
 * @final 2017年4月5日下午11:31:23
 */
class ConsoleApplication extends ApplicationBase implements WorkerHandlerInterface, DaemonHandlerInterface
{
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Console\Worker\WorkerHandlerInterface::preDispatch()
     */
    public function onWorkerPreDispatch($controller, $method, string $module = null,  bool $isMethod = true)
    {
        return $this->preDispatch($controller, $method, $module, $isMethod);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Console\Worker\WorkerHandlerInterface::onWorkerDispatch()
     */
    public function onWorkerDispatch(string $controller, string $method, string $module = null, array $args = [], bool $isMethod = true)
    {
        return $this->dispatch($controller, $method, $module, $args, $isMethod);
    }
    
    /**
     * 实现接口IDaemonHandler的日志接口
     *
     * @param string $logId 日志ID
     * @param string $message 日志内容
     * @param number $priority 权重级别
     * @see \Tiny\Console\DaemonHandlerInterface::onOutLog
     */
    public function onOutLog($logId, $log, $priority = 1)
    {
        return $this->getLogger()->log($logId, $log, $priority);
    }
    
}
?>