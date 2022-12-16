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
use Tiny\MVC\View\View;
use Tiny\Event\EventListenerInterface;
use Tiny\DI\Definition\Provider\DefinitionProvider;
use Tiny\Cache\CacheInterface;
use Tiny\Runtime\Autoloader;
use Tiny\Event\Event;
use Tiny\Runtime\ExceptionHandler;

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
     * 当前应用程序的默认命名空间
     *
     * @var string
     */
    public $namespace = 'App';
    
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
     * @var Response
     *
     */
    public $response;
    
    /**
     * 应用缓存
     *
     * @var CacheInterface
     */
    protected $applicationCache;
    
    /**
     * 事件管理器
     *
     * @var EventManager
     */
    protected $eventManager;
    
    /**
     * 初始化应用实例
     *
     * @param ContainerInterface $container 容器实例
     * @param string $path application的工作目录
     * @param string|array $profile 配置文件 为数组时多个配置文件
     */
    public function __construct(ContainerInterface $container, $path, $profile = null)
    {
        // application workdir
        $this->path = $path;
        
        // container
        $this->container = $container;
        $container->set(get_class($this), $this);
        $container->set(ApplicationBase::class, $this);
        
        // properties
        $this->profile = $profile ?: $path . DIRECTORY_SEPARATOR . 'profile.php';
        $this->properties = new Properties($this, $this->profile);
        $container->set(Properties::class, $this->properties);
        
        // container;
        $provider = $container->get(DefinitionProvider::class);
        $provider->addDefinitionFromArray([
            ApplicationProvider::class
        ]);
        $applicationProvider = $container->get(ApplicationProvider::class);
        $provider->addDefinitionProvider($applicationProvider);
        
        // init autoloader
        $this->initAutoloader($container);
        
        // event manager
        $this->eventManager = $container->get(EventManager::class);
        $this->eventManager->addEventListener($this, -1);
        
        // request & response
        $this->request = $container->get('app.request');
        $this->response = $container->get('app.response');
        
        // init EventListener
        $this->initEventListener();
        
        // event request begin
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_BEGIN_REQUEST));
    }
    
    /**
     * 添加事件监听器
     *
     * @param EventListenerInterface $eventListener 监听器实例
     * @return boolean
     */
    public function addEventListener($eventListener, int $priority = 0)
    {
        return $this->eventManager->addEventListener($eventListener, $priority);
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
        $logger = $this->get('app.logger');
        return $logger->log($id, $errLevel, $message, $extra);
    }
    
    /**
     * 异常触发事件
     *
     * @param array $exception 异常
     * @param array $exceptions 所有异常
     */
    public function onException(Event $event, \Throwable $exception, ExceptionHandler $handler)
    {
        // 配置异常通过日志方式输出
        $code = $exception->getCode();
        if ($this->properties['exception.log']) {
            $logId = $this->properties['exception.logid'];
            $this->error($logId, $code, $exception->getTraceAsString());
        }
        
        // 在response没有实例化前
        if (!$this->response) {
            return;
        }
        // 停止事件继续冒泡
        $event->stopPropagation(true);
        $this->end();
    }
    
    /**
     * 执行
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
        
        // event request end
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_END_REQUEST));
        
        // 保存已加载的类路径映射到应用缓存
        $this->saveToAutoloaderClasses();
        
        $this->response->output();
    }
    
    /**
     * 从容器中获取实例
     *
     * @param string $className 类名
     * @return mixed|\Tiny\DI\Container|\Tiny\DI\ContainerInterface
     */
    public function get(string $className)
    {
        return $this->container->get($className);
    }
    
    /**
     * 容器中是否存在某个类的实例
     *
     * @param string $className 类名
     * @return boolean
     */
    public function has(string $className)
    {
        return $this->container->has($className);
    }
    
    /**
     * 获取应用缓存
     */
    public function getApplicationCache()
    {
        return $this->has('app.cache') ? $this->get('app.cache') : true;
    }
    
    /**
     * 获取应用缓存池
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->get('app.cache');
    }
    
    /**
     * 获取配置实例
     *
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->get('app.config');
    }
    
    /**
     * 获取应用的数据源池
     *
     * @return Data
     */
    public function getData()
    {
        return $this->get('app.data');
    }
    
    /**
     * 获取应用的日志存储器
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->get('app.logger');
    }
    
    /**
     * 获取路由实例
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->get('app.router');
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
        return $this->container->get('app.dispatcher');
    }
    
    /**
     * 派发前检测
     *
     * @param string $cname
     * @param string $aname
     * @param string $mname
     * @param bool $isMethod
     */
    public function preDispatch(string $cname = null, string $aname = null, string $mname = null, bool $isMethod = false)
    {
        $cname = $cname ?: $this->request->getControllerName();
        $aname = $aname ?: $this->request->getActionName();
        $mname = $mname ?: $this->request->getModuleName();
        return $this->getDispatcher()->preDispatch($cname, $aname, $mname, $isMethod);
    }
    
    /**
     * 派发
     *
     * @access protected
     * @param string $cname 控制器名称
     * @param string $aname 动作名称
     * @param array $args 参数
     * @param bool $isEvent 是否为成员函数本身
     * @return mixed
     */
    public function dispatch(string $cname = null, string $aname = null, string $mname = null, array $args = [], bool $isMethod = false)
    {
        $cname = $cname ?: $this->request->getControllerName();
        $aname = $aname ?: $this->request->getActionName();
        $mname = $mname ?: $this->request->getModuleName();
        return $this->getDispatcher()->dispatch($cname, $aname, $mname, $args, $isMethod);
    }
    
    /**
     * 中止运行
     */
    public function end()
    {
        // event request end
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_END_REQUEST));
        if ($this->response) {
            $this->response->end();
        }
    }
    
    /**
     * 初始化应用程序的自动加载
     */
    protected function initAutoloader(ContainerInterface $container)
    {
        $autoloader = $container->get(Autoloader::class);
        
        // 添加命名空间
        $namespaces = (array)$this->properties['autoloader.namespaces'];
        foreach ($namespaces as $ns => $path) {
            $autoloader->addToNamespacePathMap($ns, $path);
        }
        
        // 获取缓存实例
        $applicationCache = $container->get(ApplicationCache::class);
        
        // 合并
        $classes = (array)$this->properties['autoloader.classes'];
        $classes += (array)$applicationCache->get('application.autoloader.classes');
        
        // 添加类路径映射
        foreach ($classes as $className => $classPath) {
            $autoloader->addToClassPathMap($className, $classPath);
        }
    }
    
    /**
     * 保存已经加载的类路径映射到缓存
     */
    protected function saveToAutoloaderClasses()
    {
        $applicationCache = $this->get(ApplicationCache::class);
        
        // 自动加载实例
        $autoloader = $this->get(Autoloader::class);
        $loadedClasses = (array)$autoloader->getLoadedClassMap();
        
        // 已经缓存的数据
        $classes = (array)$applicationCache->get('application.autoloader.classes');
        
        // 需要更新到缓存的类映射
        $updateClasses = [];
        foreach ($loadedClasses as $className => $path) {
            if (!key_exists($className, $classes)) {
                $updateClasses[$className] = $path;
            }
        }
        if ($updateClasses) {
            $applicationCache->set('application.autoloader.classes', array_merge($classes, $updateClasses));
        }
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
            $this->eventManager->addEventListener($eventListener, -10000);
        }
    }
    
    /**
     * 引导
     */
    protected function bootstrap()
    {
        // 判断是否进行引导类加载和触发引导事件
        if (!$this->properties['bootstrap.enabled']) {
            return;
        }
        
        // 获取配置的引导类class
        $eventListener = $this->properties['bootstrap.event_listener'];
        if (!is_string($eventListener) && !is_array($eventListener)) {
            throw new ApplicationException('properties.bootstrap.eventListeners must be an array type or a string type class name');
        }
        // 加入监听onBootstrap事件
        $this->eventManager->addEventListener($eventListener);
        
        // 触发onBootstrap事件
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_BOOTSTRAP));
    }
    
    /**
     * 执行路由n
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
        $this->eventManager->triggerEvent(new MvcEvent(MvcEvent::EVENT_ROUTER_SHUTDOWN, [
            'matchedRoute' => $matchedRoute
        ]));
        return $matchedRoute;
    }
}
?>