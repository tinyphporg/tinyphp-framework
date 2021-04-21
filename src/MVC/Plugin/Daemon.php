<?php
/**
 * @Copyright (C), 2013-, King.
 * @Name Daemon.php
 * @Author King
 * @Version Beta 1.0
 * @Date 2020年6月3日下午2:32:51
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年6月3日下午2:32:51 第一次建立该文件
 *                 King 2020年6月3日下午2:32:51 修改
 *
 */
namespace Tiny\MVC\Plugin;
use Tiny\Config\Configuration;
use Tiny\MVC\ApplicationBase;
use Tiny\MVC\ApplicationException;
/**
 *
 *
 * @package class
 * @since 2020年6月3日下午2:38:57
 * @final 2020年6月3日下午2:38:57
 */
class Daemon implements Iplugin
{

    /**
     * 当前应用实例
     *
     * @var \Tiny\MVC\ApplicationBase
     */
    protected $_app;

    /**
     * app属性
     * @var Configuration
     */
    protected $_properties;

    /**
     * 初始化
     *
     * @param $app ApplicationBase
     *        当前应用实例
     * @return void
     */
    public function __construct(ApplicationBase $app)
    {
        $this->_app = $app;
        $this->_properties = $app->properties;
    }

    /**
     * 本次请求初始化时发生的事件
     *
     * @return void
     */
    public function onBeginRequest()
    {
    }

    /**
     * 本次请求初始化结束时发生的事件
     *
     * @return void
     */
    public function onEndRequest()
    {

    }

    /**
     * 执行路由前发生的事件
     *
     * @return void
     */
    public function onRouterStartup()
    {
        if (!$this->_app->request->param['d'] && !$this->_app->request->param['daemon'])
        {
            return;
        }
        $action = $this->_app->request->param['daemon'] ?: 'start';
        $config = $this->_app->properties['daemon'];
        if(!$config['enable'])
        {
            return;
        }
        
        $config['debug'] = $this->_app->isDebug;
        $id = $this->_app->request->param['id'] ?: $config['id'];
        if (!$id)
        {
            throw new ApplicationException('daemon init failed!: option --id is null or not set', E_ERROR);
        }
        
        if (!isset($config['policys'][$id]))
        {
            throw new ApplicationException('daemon init failed!:  option --id:%s in policys is null', E_ERROR);
        }

        $policy = $config['policys'][$id];
        $policy['id'] = $id;
        if (!$policy || !is_array($policy))
        {
            return;
        }

        $options['action'] = $action;
        $options['piddir'] = $config['piddir'];
        $options['logdir'] = $config['logdir'];
        if (is_array($policy['options']))
        {
            $options = array_merge($options, $policy['options']);
        }
        if (is_array($policy['workers']))
        {
            $workers = $policy['workers'];
        }
        $daemonInstance = new \Tiny\Console\Daemon($id, $options);
        $daemonInstance->addWorkerByConfig($workers, $this->_app);
        $daemonInstance->setDaemonHandler($this->_app);
        
        $daemonInstance->run();
        $this->_app->response->end();

    }

    /**
     * 执行路由后发生的事件
     *
     * @return void
     */
    public function onRouterShutdown()
    {

    }

    /**
     * 执行分发前发生的动作
     *
     * @return void
     */
    public function onPreDispatch()
    {
    }

    /**
     * 执行分发后发生的动作
     *
     * @return void
     */
    public function onPostDispatch()
    {
    }
}
?>