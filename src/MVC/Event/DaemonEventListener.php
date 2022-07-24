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
namespace Tiny\MVC\Event;

use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\Application\ConsoleApplication;
use Tiny\MVC\Request\ConsoleRequest;
use Tiny\MVC\Response\ConsoleResponse;
use Tiny\MVC\Application\Properties;
use Tiny\MVC\Application\ApplicationException;
use Tiny\Console\Daemon;

/**
 * 守护进程监听器
 *
 * @package Tiny.MVC.Event.Listener
 * @since 2022年2月4日下午10:17:22
 * @final 2022年2月4日下午10:17:22
 */
class DaemonEventListener implements DispatchEventListenerInterface
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
     * @see \Tiny\MVC\Event\RouteEventListenerInterface::onRouterStartup()
     */
    public function onPreDispatch(MvcEvent $event, array $params)
    {
        $param = $this->request->param;
        
        // 监听 -d --daemon参数
        if (!$param['d'] && !$param['daemon']) {
            return;
        }
        $daemonAction = $param['daemon'] ?: 'start';
        foreach ($param as $k => $v) {
            if (is_int($k) && in_array($v, ['start', 'stop', 'restart'])) {
                $daemonAction = $v;
                break;
            }
        }

        // properties.daemon
        $config = $this->properties['daemon'];
        if (!$config['enabled']) {
            return;
        }

        // debug
        $daemons = (array)$config['daemons'];
        if (!$daemons) {
            throw new ApplicationException('daemon init failed!: config.daemons is null or not set', E_ERROR);
        }
        // properties.daemon.id
        $id = $this->request->param['id'] ?: ((string)$config['id'] ?: key($daemons));
        if (!$id) {
            throw new ApplicationException('daemon init failed!: option --id is null or not set', E_ERROR);
        }
        if (!key_exists($id, $daemons)) {
            throw new ApplicationException('daemon init failed!:  option --id:%s in policys is null', E_ERROR);
        }
        
        // properties.daemon.config
        $daemonConfig = $daemons[$id];
        if (!$daemonConfig || !is_array($daemonConfig)) {
            return;
        }
        $daemonConfig['id'] = $id;
        
        $options = [];
        $options['action'] = $daemonAction;
        $options['piddir'] = $config['piddir'];
        $options = array_merge($options, (array)$daemonConfig['options']);
        
        $workers = (array)$daemonConfig['workers'];
        if (!$workers) {
            return;
        }
        
        // Daemon
        $daemonInstance = new Daemon($id, $options);  // 创建实例
        $daemonInstance->addWorkersByConfig($workers, $this->app);  // 设置子进程
        $daemonInstance->setDaemonHandler($this->app);  // 设置子进程执行守护的委托句柄
        $daemonInstance->run();   // 执行守护
        $this->response->end();  // 终止
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\RouteEventListenerInterface::onRouterShutdown()
     */
    public function onPostDispatch(MvcEvent $event, array $params)
    {
    }
}
?>