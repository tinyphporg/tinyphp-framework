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
namespace Tiny\MVC;

use Tiny\MVC\Response\ConsoleResponse;
use Tiny\MVC\Request\ConsoleRequest;
use Tiny\Console\Worker\IWorkerHandler;
use Tiny\Console\IDaemonHandler;

/**
 * 命令行应用实例
 *
 * @package Tiny.Application
 * @since 2017年4月5日下午11:31:23
 * @final 2017年4月5日下午11:31:23
 */
class ConsoleApplication extends ApplicationBase implements IWorkerHandler, IDaemonHandler
{

    /**
     * 实现worker接口 回调继续执行application的派发动作
     *
     * {@inheritdoc}
     * @see \Tiny\Console\Worker\IWorkerHandler::onWorkerDispatch()
     */
    public function onWorkerDispatch($controller, $action, $args, $isEvent = FALSE)
    {
        return $this->dispatch($controller, $action, $args, $isEvent);
    }

    /**
     * 实现接口IDaemonHandler的日志接口
     *
     * @param string $logId
     *        日志ID
     * @param string $message
     *        日志内容
     * @param number $priority
     *        权重级别
     * @see \Tiny\Console\IDaemonHandler::onOutLog
     */
    public function onOutLog($logId, $log, $priority = 1)
    {
        return $this->getLogger()->log($logId, $log, $priority);
    }

    /**
     * 初始化属性实例
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\ApplicationBase::_initProperties()
     */
    protected function _initProperties()
    {
        parent::_initProperties();
        $this->_initBuilder();   //打包
        $this->_initDaemon();    //守护进程
        $this->_initUIInstaller(); //UI安装
    }
    
    /**
     * 初始化请求实例
     *
     * @return void
     */
    protected function _initRequest()
    {
        $this->request = ConsoleRequest::getInstance();
        parent::_initRequest();
    }
    
    /**
     * 初始化debug模块
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\ApplicationBase::_initDebug()
     */
    protected function _initDebug()
    {
        if(!$this->properties['debug']['enabled'] && (bool)$this->request->param['debug'])
        {
            $this->properties['debug']['enabled'] = TRUE;
        }
        parent::_initDebug();
    }

    /**
     * 初始化响应实例
     *
     * @return void
     */
    protected function _initResponse()
    {
        $this->response = ConsoleResponse::getInstance();
    }
    
    
    /**
     *  初始化WEB环境下的tinyphp-ui
     *  
     * @param array $config
     */
    protected function _initUIInstaller()
    {
        $config = $this->properties['view.ui'];
        if (!$config || !$config['enabled'])
        {
            return;
        }
        $iconfig = (array)$config['installer'];
        if(!$iconfig || !$iconfig['plugin'])
        {
            return;
        }
        $this->properties['plugins.ui_installer'] = (string)$iconfig['plugin'];
    }
    
    /**
     * 初始化打包器插件
     *
     * @param array $config
     *        properties.build配置节点数据
     */
    protected function _initBuilder()
    {
        $config = $this->properties['build'];
        if (!$config || !$config['enabled'] || !$config['plugin'])
        {
            return;
        }
        $this->properties['plugins.build'] = (string)$config['plugin'];
    }

    /**
     * 初始化服务端插件
     *
     * @param array $config
     *        properties.daemon配置节点数据
     */
    protected function _initDaemon()
    {
        $config = $this->properties['daemon'];
        if (!$config || !$config['enabled'] || !$config['plugin'])
        {
            return;
        }
        $daemonId = $this->request->param['id'] ?: $this->properties['daemon.id'];
        $this->properties['exception.logid'] = $daemonId . '.err';
        $this->properties['plugins.daemon'] = (string)$config['plugin'];
    }
}
?>