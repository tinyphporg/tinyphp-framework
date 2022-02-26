<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Application.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月26日下午6:38:24 0 第一次建立该文件
 *          King 2021年11月26日下午6:38:24 1 修改
 *          King 2021年11月26日下午6:38:24 stable 1.0.01 审定
 */
namespace Tiny\MVC\Application;

use Tiny\Config\Configuration;
use Tiny\DI\ContainerInterface;
use Tiny\Data\Data;
use Tiny\Tiny;
use Tiny\MVC\Router\Router;
use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;
use Tiny\Log\Logger;
use Tiny\Cache\Cache;
use Tiny\Event\EventManager;
use Tiny\DI\Container;
use Tiny\Event\ExceptionEventListener;
use Tiny\MVC\Event\MvcEvent;
use Tiny\MVC\Controller\Dispatcher;
use Tiny\MVC\View\View;

/**
 * app实例基类
 *
 * @author King
 * @package Tiny.MVC
 * @since 2013-3-21下午04:55:41
 * @final 2017-3-11下午04:55:41
 */
abstract class ApplicationBase implements ExceptionEventListener
{
    
    /**
     * 应用实例的插件触发事件集合
     *
     * @var array
     */
    const PLUGIN_EVENTS = [
        'onbeginrequest',
        'onendrequest',
        'onrouterstartup',
        'onroutershutdown',
        'onpredispatch',
        'onpostdispatch',
        'onexception'
    ];
    
    /**
     * APP所在的目录路径
     *
     * @var string
     *
     */
    public $path;
    
    /**
     * App配置文件路径
     *
     * @var string
     *
     */
    public $profile;
    
    /**
     * 是否为调试模式
     *
     * @var bool
     */
    public $isDebug = false;
    
    /**
     * public
     *
     * @var Configuration App的基本配置类
     *     
     */
    public $properties;
    
    /**
     * 容器实例
     *
     * @var Container
     */
    public $container;
    
    /**
     * 当前请求实例
     *
     * @var Request
     *
     */
    public $request;
    
    /**
     * 当前响应实例
     *
     * @var string WebResponse
     *     
     */
    public $response;
    
    /**
     * 事件管理器
     *
     * @var EventManager
     */
    protected $eventManager;

    /**
     * 初始化应用实例
     *
     * @param string $profile 配置文件路径
     * @return void
     */
    public function __construct(ContainerInterface $container = null, $path, $profile = null)
    {
        // application workdir
        $this->path = $path;
        
        // container
        $this->container = $container;
        $this->container->set(self::class, $this);
        $this->container->set(ApplicationBase::class, $this);
        
        // properties
        $this->profile = $profile ?: $path . DIRECTORY_SEPARATOR . 'profile.php';
        $this->properties = new Properties($this, $this->profile);
                
        // init
        $this->init();
    }
    
    /**
     * 添加事件监听器
     *
     * @param string $eventListener 监听器名称
     *        EventListenerInterface 监听器实例
     * @return boolean
     */
    public function addEventListener($eventListener)
    {
        return $this->eventManager->addEventListener($eventListener);
    }
    
    /**
     * 日志
     *
     * @param string $id 日志ID
     * @param string $message 日志信息
     * @param number $priority 日志级别
     * @param array $extra 附加信息
     * @return mixed
     */
    public function log($id, $message, $priority = 1, $extra = [])
    {
        $logger = $this->get(Logger::class);
        return $logger->log($id, $message, $priority, $extra);
    }
    
    /**
     * 错误日志
     *
     * @param string $id 日志ID
     * @param int $errLevel
     * @param string $message
     * @param array $extra
     * @return mixed
     */
    public function error($id, $errLevel, $message, $extra = [])
    {
        $logger = $this->get(Logger::class);
        return $logger->log($id, $errLevel, $message, $extra);
    }
    
    /**
     * 异常触发事件
     *
     * @param array $exception 异常
     * @param array $exceptions 所有异常
     * @return void
     */
    public function onException(array $exception, array $exceptions)
    {
            if ($this->properties['exception.log']) {
                $logId = $this->properties['exception.logid'];
                $logMsg = $exception['handle'] . ':' . $exception['message'] . ' from ' . $exception['file'] . ' on line ' . $exception['line'];
                $this->error($logId, $exception['level'], $logMsg);
            }
            
            if ($exception['isThrow']) {
                // event postdispatch
                $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_POST_DISPATCH));
                $this->response->output();
            }
    }
    
    /**
     * 执行
     *
     * @return void
     */
    public function run()
    {
        $this->bootstrap();
        
        $this->route();
        
        // event predispatch
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_PRE_DISPATCH));
        
        $this->dispatch();
        
        // event postdispatch
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_POST_DISPATCH));
        
        // event endrequest
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_END_REQUEST));
        
        $this->response->output();
    }
    
    /**
     * 从容器中获取实例
     *
     * @param string $name
     * @return mixed|\Tiny\DI\Container|\Tiny\DI\ContainerInterface
     */
    public function get(string $name)
    {
        return $this->container->get($name);
    }

    /**
     * 获取应用缓存池
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->get(Cache::class);
    }
    
    /**
     * 获取配置实例
     * 
     * @return Configuration
     */
    public function getConfig() 
    {
        return $this->get(Configuration::class);    
    }
    
    /**
     * 获取应用的数据源池
     *
     * @return Data
     */
    public function getData()
    {
        return $this->get(Data::class);
    }
    
    /**
     * 获取应用的日志存储器
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->get(Logger::class);
    }
    
    /**
     * 获取路由实例
     * @return Router
     */
    public function getRouter()
    {
        return $this->get(Router::class);    
    }
    
    /**
     * 获取视图实例
     *
     * @return View
     */
    public function getView()
    {
        return $this->container->get(View::class);
    }
    
    /**
     * 获取派发器实例
     */
    public function getDispatcher()
    {
        return $this->container->get(Dispatcher::class);
    }
    
    /**
     * 派发
     *
     * @access protected
     * @param string $cname 控制器名称
     * @param string $aname 动作名称
     * @return mixed
     */
    public function dispatch(string $cname = null, string $aname = null, array $args = [], bool $isEvent = false)
    {
        $cname = $cname ?: $this->request->getControllerName();
        $aname = $aname ?: $this->request->getActionName();
        return $this->getDispatcher()->dispatch($cname, $aname, $args, $isEvent);
    }
    
    /**
     * 中止运行
     */
    public function end()
    {
        // event postdispatch
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_POST_DISPATCH));
        $this->response->end();
    }
    
    /**
     * 初始化
     */
    protected function init()
    {
        // event manager
        $this->eventManager = $this->container->get(EventManager::class);
        $this->eventManager->addEventListener($this);
        
        // request
        $this->request = $this->container->get(Request::class);
        $this->response = $this->container->get(Response::class);
        
        // init EventListener
        $this->initEventListener();
        
        // event begin resquest
        $eventBeginRequest = new MvcEvent(MvcEvent::EVENT_BEGIN_REQUEST, []);
        $this->eventManager->triggerEvent($eventBeginRequest);
    }
    
    /**
     * 初始化事件监听器
     */
    protected function initEventListener()
    {
        $config = $this->properties['event'];
        if (!$config['enabled']) {
            return;
        }
        
        $listeners = (array)$config['listeners'];
        foreach ($listeners as $eventListener) {
            $this->eventManager->addEventListener($eventListener);
        }
    }
    
    /**
     * 引导
     *
     * @return void
     */
    protected function bootstrap()
    {
        // bootstrap
        if (!$this->properties['bootstrap.enabled']) {
            return;
        }
        
        $eventListeners = $this->properties['bootstrap.eventListeners'];
        if (is_string($eventListeners)) {
            $eventListeners = [
                $eventListeners
            ];
        }
        if (!is_array($eventListeners)) {
            throw new ApplicationException('properties.bootstrap.eventListeners must be an array type or a string type class name');}
        
        $this->eventManager->addEventListener($eventListeners);
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_BOOTSTRAP));
    }
    
    /**
     * 执行路由n
     *
     */
    protected function route()
    {
        // event startup
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_ROUTER_STARTUP));
        $routerInstance = $this->getRouter();
        if (!$routerInstance) {
            return;
        }
        
        // exec route
        $matchedRoute = $routerInstance->route();
        
        // event shutdown
        $this->eventManager->triggerEvent(
            new MvcEvent(MvcEvent::EVENT_ROUTER_SHUTDOWN, [
                'matchedRoute' => $matchedRoute
            ]));
        return $matchedRoute;
    }
}
?>