<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name DaemonEventListener.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月4日下午10:14:21
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月4日下午10:14:21 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event\Plugin;

use Tiny\MVC\Event\MvcEvent;
use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\Application\ConsoleApplication;
use Tiny\MVC\Request\ConsoleRequest;
use Tiny\MVC\Response\ConsoleResponse;
use Tiny\MVC\Application\Properties;
use Tiny\MVC\Application\ApplicationException;
use Tiny\MVC\Event\Listener\RouteEventListener;

/**
 * 守护进程监听器
 *
 * @package Tiny.MVC.Event.Listener
 * @since 2022年2月4日下午10:17:22
 * @final 2022年2月4日下午10:17:22
 */
class Daemon implements RouteEventListener
{
    
    /**
     * 当前命令行应用实例
     *
     * @var ConsoleApplication
     */
    protected $app;
    
    /**
     * 当前应用的属性配置实例
     *
     * @var Properties
     */
    protected $properties;
    
    /**
     * 当前应用的请求实例
     *
     * @var ConsoleRequest
     */
    protected $request;
    
    /**
     * 当前应用的响应实例
     *
     * @var ConsoleResponse
     */
    protected $response;
    
    /**
     *
     * @param ConsoleApplication $app
     */
    public function __construct(ApplicationBase $app)
    {
        $this->app = $app;
        $this->properties = $app->properties;
        $this->request = $app->request;
        $this->response = $app->response;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\Listener\RouteEventListener::onRouterStartup()
     */
    public function onRouterStartup(MvcEvent $event, array $params)
    {
        // 监听 -d --daemon参数
        if (!$this->request->param['d'] && !$this->request->param['daemon']) {
            return;
        }
        
        // daemon action
        $action = $this->request->param['daemon'] ?: 'start';
        
        // properties.daemon
        $config = $this->properties['daemon'];
        if (!$config['enabled']) {
            return;
        }
        
        // debug
        $config['debug'] = $this->app->isDebug;
        
        // properties.daemon.id
        $id = $this->request->param['id'] ?: $config['id'];
        if (!$id) {
            throw new ApplicationException('daemon init failed!: option --id is null or not set', E_ERROR);
        }
        
        if (!isset($config['policys'][$id])) {
            throw new ApplicationException('daemon init failed!:  option --id:%s in policys is null', E_ERROR);
        }
        
        // properties.daemon.policys
        $policy = $config['policys'][$id];
        $policy['id'] = $id;
        if (!$policy || !is_array($policy)) {
            return;
        }
        
        $options['action'] = $action;
        $options['piddir'] = $config['piddir'];
        $options['logdir'] = $config['logdir'];
        if (is_array($policy['options'])) {
            $options = array_merge($options, $policy['options']);
        }
        if (is_array($policy['workers'])) {
            $workers = $policy['workers'];
        }
        
        // Daemon
        $daemonInstance = new \Tiny\Console\Daemon($id, $options);
        $daemonInstance->addWorkerByConfig($workers, $this->app);
        $daemonInstance->setDaemonHandler($this->app);
        $daemonInstance->run();
        $this->response->end();
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\Listener\RouteEventListener::onRouterShutdown()
     */
    public function onRouterShutdown(MvcEvent $event, array $params)
    {
    }
}
?>