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
     * 初始化响应实例
     *
     * @return void
     */
    protected function _initResponse()
    {
        $this->response = ConsoleResponse::getInstance();
    }

    /**
     * 重载初始化插件
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\ApplicationBase::_initPlugin()
     */
    protected function _initPlugin()
    {
        if(!$this->properties['debug']['enabled'] && (bool)$this->request->param['debug'])
        {
            $this->isDebug = TRUE;
        }
        parent::_initPlugin();
        $this->_initPluginUI($this->_prop['view']['ui']);
        $this->_initPluginBuilder($this->_prop['build']);
        $this->_initDaemonPlugin($this->_prop['daemon']);
    }
    
    /**
     *  初始化WEB环境下的tinyphp-ui
     *  
     * @param array $config
     */
    protected function _initPluginUI($config)
    {
        if (!$config || !$config['enabled'])
        {
            return;
        }
        $installer = (array)$config['installer'];
        if(!$installer)
        {
            return;
        }
        $className = (string)$installer['plugin'];
        if ($className && class_exists($className))
        {
            $uiInstance = new $className($this);
        }
        else
        {
            $uiInstance =  new \Tiny\MVC\View\UI\UIInstaller($this);
        }
        $this->regPlugin($uiInstance);
    }
    
    /**
     * 初始化打包器插件
     *
     * @param array $config
     *        properties.build配置节点数据
     */
    protected function _initPluginBuilder($config)
    {
        if (!$config || !$config['enabled'])
        {
            return;
        }

        $className = (string)$config['plugin'];
        if ($className && class_exists($className))
        {
            $builderInstance = new $className($this);
        }
        else
        {
            $builderInstance = new \Tiny\MVC\Plugin\Builder($this);
        }
        $this->regPlugin($builderInstance);
    }

    /**
     * 初始化服务端插件
     *
     * @param array $config
     *        properties.daemon配置节点数据
     */
    protected function _initDaemonPlugin($config)
    {
        if (!$config || !$config['enabled'])
        {
            return;
        }

        $className = (string)$config['plugin'];
        if ($className && class_exists($className))
        {
            $daemonInstance = new $className($this);
        }
        else
        {
            $daemonInstance = new \Tiny\MVC\Plugin\Daemon($this);
        }
        $daemonId = $this->request->param['id'] ?: $this->_prop['daemon']['id'];
        $this->_prop['exception']['logid'] = $daemonId . '.err';
        $this->regPlugin($daemonInstance);
    }
}
?>