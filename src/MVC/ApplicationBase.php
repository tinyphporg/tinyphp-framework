<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月8日下午4:04:15
 * @Class List
 * @Function List
 * @History King 2017年3月8日下午4:04:15 0 第一次建立该文件
 *          King 2017年3月8日下午4:04:15 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC;

use Tiny\Runtime\IExceptionHandler;
use Tiny\Config\Configuration;
use Tiny\Tiny;
use Tiny\Log\Logger;
use Tiny\Cache\Cache;
use Tiny\Data\Data;
use Tiny\Lang\Lang;
use Tiny\MVC\Router\IRouter;
use Tiny\MVC\Controller\Controller;
use Tiny\MVC\View\View;
use Tiny\MVC\Plugin\Iplugin;
use Tiny\MVC\Bootstrap\Base as BootstrapBase;
use Tiny\MVC\Router\Router;
use Tiny\MVC\Controller\Base;
use Tiny\Runtime\Runtime;
use Tiny\Runtime\Environment;
use Tiny\Filter\IFilter;
use Tiny\Filter\Filter;
use Tiny\Runtime\RuntimeCacheItem;
use Tiny\MVC\Router\RouterException;
use Tiny\MVC\Application\Properties;
use Tiny\DI\Container;
use Tiny\Runtime\Autoloader;
use Tiny\Runtime\ExceptionHandler;
use Tiny\Runtime\RuntimeCachePool;
use Tiny\DI\DefintionProivder;

// MVC下存放资源的文件夹
const TINY_MVC_RESOURCES = __DIR__ . '/_resources/';

/**
 * app实例基类
 *
 * @author King
 * @package Tiny.MVC
 * @since 2013-3-21下午04:55:41
 * @final 2017-3-11下午04:55:41
 */
abstract class ApplicationBase implements IExceptionHandler
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
    public $isDebug = FALSE;
    
    /**
     * 默认语言
     *
     * @var string
     */
    public $charset = 'zh_cn';
    
    /**
     * 默认时区
     *
     * @var string
     */
    public $timezone = 'PRC';
    
    /**
     *
     * @var Runtime
     */
    public $runtime;
    
    /**
     * 运行时参数
     *
     * @var Environment
     */
    public $env;
    
    /**
     * public
     *
     * @var Configuration App的基本配置类
     *
     */
    public $properties;
    
    /**
     * 容器实例
     * @var Container
     */
    public $container;
    
    /**
     * 当前请求实例
     *
     * @var string WebRequest
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
     * 当前路由器
     *
     * @var IRouter
     */
    public $router;
    
    /**
     * 引导类
     *
     * @var BootStrapBase
     *
     */
    protected $_bootstrap;
    
    /**
     * 路由器实例
     *
     * @var Router
     */
    protected $_router;
    
    /**
     * 配置实例
     *
     * @var Configuration
     */
    protected $_config;
    
    /**
     * 缓存实例
     *
     * @var Cache
     */
    protected $cache;
    
    /**
     * 设置数据池实例
     *
     * @var Data
     */
    protected $_data;
    
    /**
     * 语言包实例
     *
     * @var Lang
     */
    protected $_lang;
    
    /**
     * 日志实例
     *
     * @var Logger
     */
    protected $_logger;
    
    /**
     * 视图实例
     *
     * @var View
     */
    protected $_view;
    
    /**
     * 过滤
     *
     * @var \Tiny\Filter\Filter
     */
    protected $_filter;
    
    /**
     * debug实例
     *
     * @var \Tiny\MVC\Plugin\Debug
     */
    protected $_debug;
    
    /**
     * 控制器的缓存实例数组
     *
     * @var Controller
     */
    protected $_controllers = [];
    
    /**
     * 模型实例数组
     *
     * @var \Tiny\MVC\Model\Base
     */
    protected $_models = [];
    
    /**
     * 应用程序运行的时间戳
     *
     * @var int timeline
     */
    protected $_startTime = 0;
    
    /**
     * Application注册的插件
     *
     * @var array
     *
     */
    protected $_plugins = [];
    
    /**
     * 配置数组
     *
     * @var Array
     */
    protected $_prop;
    
    /**
     * 运行时缓存
     *
     * @var RuntimeCacheItem
     */
    protected $_runtimeCache;
    
    /**
     * model搜索节点列表
     *
     * @var array
     */
    protected $_modelSearchNodes = FALSE;
    
    /**
     * 初始化应用实例
     *
     * @param string $profile 配置文件路径
     * @return void
     */
    public function __construct(Runtime $runtime = null, $path, $profile = null)
    {
        $this->runtime = $runtime;
        $this->runtime->setApplication($this);
        $this->env = $this->runtime->env;     
        
        $this->_runtimeCache =  $this->runtime->getApplicationCache();
        
        /*应用实例路径配置和初始化*/
        $this->path = $path;
        $this->profile = $profile ?: $path . DIRECTORY_SEPARATOR . 'profile.php';
        $this->properties = new Properties($this->profile, $this);       
        $this->isDebug = $this->properties['debug.enabled'];
        $this->_init();
    }
    
    /**
     * 设置引导类
     *
     * @param BootstrapBase $bootStrap 继承了BootstrapBase的引导类实例
     * @return ApplicationBase
     */
    public function setBootstrap(BootstrapBase $bootStrap)
    {
        $this->_bootstrap = $bootStrap;
        return $this;
    }
    
    /**
     * 设置路由器
     *
     * @param Router $router 路由器
     * @return ApplicationBase
     */
    public function setRouter(Router $router)
    {
        $this->_router = $router;
        return $this;
    }
    
    /**
     *
     * @return Router
     *
     */
    public function getRouter()
    {
        if (!$this->_router)
        {
            $isConsolemode = $this->env->isConsole();
            $this->_router = new Router($this->request, $isConsolemode);
        }
        return $this->_router;
    }

    /**
     * 设置应用过滤器
     *
     * @param IFilter $filter 过滤器实例
     */
    public function setFilter(IFilter $filter)
    {
        $this->_filter = $filter;
        return $this->_filter;
    }
    
    /**
     * 获取过滤器
     *
     * @throws ApplicationException
     * @return \Tiny\Filter\Filter
     */
    public function getFilter()
    {
        if ($this->_filter)
        {
            return $this->_filter;
        }
        $prop = $this->_prop['filter'];
        if (!$prop['enabled'])
        {
            return NULL;
        }
        
        $this->_filter = Filter::getInstance();
        if ($this->env['RUNTIME_MODE'] == $this->env['RUNTIME_MODE_WEB'] && $prop['web'])
        {
            $this->_filter->addFilter($prop['web']);
        }
        if ($this->env['RUNTIME_MODE'] == $this->env['RUNTIME_MODE_CONSOLE'] && $prop['console'])
        {
            $this->_filter->addFilter($prop['console']);
        }
        if ($this->env['RUNTIME_MODE'] == $this->env['RUNTIME_MODE_RPC'] && $prop['rpc'])
        {
            $this->_filter->addFilter($prop['rpc']);
        }
        if (is_array($prop['filters']))
        {
            foreach ($prop['filters'] as $fname)
            {
                $this->_filter->addFilter($fname);
            }
        }
        return $this->_filter;
    }
    
    /**
     * 设置日志实例
     *
     * @param Logger $logger 日志实例
     * @return self
     */
    public function setLogger(Logger $logger)
    {
        $this->_logger = $logger;
        return $this;
    }
    
    /**
     * 获取日志对象
     *
     * @return Logger
     */
    public function getLogger()
    {
        if ($this->_logger)
        {
            return $this->_logger;
        }
        $prop = $this->_prop['log'];
        if (!$prop['enabled'])
        {
            throw new ApplicationException("properties.log.enabled is false!");
        }
        $this->_logger = Logger::getInstance();
        $prop['drivers'] = $prop['drivers'] ?: [];
        foreach ($prop['drivers'] as $type => $className)
        {
            Logger::regWriter($type, $className);
        }
        $policy = ('file' == $prop['type']) ? [
            'path' => $prop['path']
        ] : [];
        $this->_logger->addWriter($prop['type'], $policy);
        return $this->_logger;
    }
    
    /**
     * 异常触发事件
     *
     * @param array $exception 异常
     * @param array $exceptions 所有异常
     * @return void
     */
    public function onException($e, $exceptions)
    {
        $isLog = $this->_prop['exception']['log'];
        $logId = $this->_prop['exception']['logid'];
        if ($isLog)
        {
            $logMsg = $e['handle'] . ':' . $e['message'] . ' from ' . $e['file'] . ' on line ' . $e['line'];
            $this->getLogger()->error($logId, $e['level'], $logMsg);
        }
        if ($e['isThrow'])
        {
            $this->onPostDispatch();
            $this->response->output();
        }
    }
    
    /**
     * 简单获取控制器
     *
     * @param string $cName 模型名称
     * @return Base
     */
    public function getController($cname)
    {
        $cname = $cname ?: $this->request->getController();
        if ($this->_controllers[$cname])
        {
            return $this->_controllers[$cname];
        }
        $cparam = preg_replace_callback("/\b\w/", function ($param) {
            return strtoupper($param[0]);
        }, $cname);
            
            
            $cparam = "\\" . preg_replace("/\/+/", "\\", $cparam);
            $controllerName = '\App\Controller' . $cparam;
            if (!class_exists($controllerName))
            {
                throw new ApplicationException("Dispatch errror:controller,{$controllerName}不存在，无法加载", E_NOFOUND);
            }
            
            $controllerInstance = new $controllerName();
            if (!$controllerInstance instanceof \Tiny\MVC\Controller\Base)
            {
                throw new ApplicationException("Controller:'{$controllerName}' is not instanceof Tiny\MVC\Controlller\Controller!", E_NOFOUND);
            }
            $controllerInstance->setApplication($this);
            $this->_controllers[$cname] = $controllerInstance;
            return $controllerInstance;
    }
    
    /**
     * 获取已经加载的控制器列表
     */
    public function getControllerList()
    {
        return $this->_controllers;
    }
    
    /**
     * 获取动作名称
     *
     * @param string $aname
     */
    public function getAction($aname, bool $isEvent = FALSE)
    {
        $aname = $aname ?: $this->request->getAction();
        $aname = $isEvent ? $aname : $aname . 'Action';
        return $aname;
    }
    
    /**
     * 简单获取模型
     *
     * @param string $modelName 模型名称
     * @return \Tiny\MVC\Model\Base
     */
    public function getModel($mname)
    {
        if ($this->_models[$mname])
        {
            return $this->_models[$mname];
        }
        $modelFullName = $this->_searchModel($mname);
        if ($modelFullName)
        {
            $this->_models[$mname] = new $modelFullName();
            return $this->_models[$mname];
        }
    }
    
    /**
     * 获取已经加载的model列表
     *
     * @return \Tiny\MVC\Model\Base
     */
    public function getModels()
    {
        return $this->_models;
    }
    
    /**
     * 设置默认的时区
     *
     *
     * @param string $timezone 时区标示
     * @return bool
     */
    public function setTimezone($timezone)
    {
        $this->timezone = (string)$timezone ?: 'PRC';
        return date_default_timezone_set($timezone);
    }
    
    /**
     * 获取已经设置的默认时区
     *
     *
     * @return string
     */
    public function getTimezone()
    {
        return date_default_timezone_get();
    }
    
    /**
     * 注册插件
     *
     *
     * @param Iplugin $plugin 实现插件接口的实例
     * @return self
     */
    public function regPlugin(Iplugin $plugin)
    {
        $this->_plugins[] = $plugin;
        return $this;
    }
    
    /**
     * 执行
     *
     * @return void
     */
    public function run()
    {
        $this->_bootstrap();
        $this->onRouterStartup();
        $this->_route();
        $this->onRouterShutdown();
        $this->onPreDispatch();
        $this->dispatch();
        $this->onPostDispatch();
        $this->response->output();
    }
    
    /**
     * 中止运行
     *
     */
    public function end()
    {
        $this->onPostDispatch();
        $this->response->end();
    }
    
    /**
     * 分发
     *
     * @access protected
     * @param string $cname 控制器名称
     * @param string $aname 动作名称
     * @return mixed
     */
    public function dispatch(string $cname = NULL, string $aname = NULL, array $args = [])
    {
        
        // 获取控制器实例
        $controller = $this->getController($cname);
        $this->controller = $controller;
        
        // 获取执行动作名称
        $action = $this->getAction($aname, FALSE);

      
        // 触发事件
        if (method_exists($controller, $action))
        {
           // return call_user_func_array([$controller, $action], $args);
        }

        // 执行前返回FALSE则不执行派发动作
        $ret = call_user_func_array([$controller, 'onBeginExecute'], $args);
        if (FALSE === $ret)
        {
            return FALSE;
        }
        
        if (!method_exists($controller, $action))
        {
            $cname = get_class($controller);
            $aname = $action;
            throw new ApplicationException("Dispatch error: The Action '{$aname}' of Controller '{$cname}' is not exists ", E_NOFOUND);
        }
        $ret = $this->container->call([$controller, $action]);
       // return $ret;  
       // $ret = call_user_func_array([$controller, $action], $args);
        call_user_func_array([$controller, 'onEndExecute'], $args);
        return $ret;
    }
    
    /**
     * 运行插件
     *
     * @param string $method 插件事件
     * @param $params array 参数
     * @return void
     */
    public function __call($method, $params)
    {
        $method = strtolower($method);
        if (in_array($method, static::PLUGIN_EVENTS))
        {
            return $this->_onPlugin($method, $params);
        }
    }
    
    /**
     * 执行初始化
     *
     * @return void
     */
    protected function _init()
    {
        $this->initContainer();
        $this->_initProperties();
        $this->_initResponse();
        $this->_initImport();
        $this->_initException();
        $this->_initRequest();
        $this->_initPlugin();
        $this->_prop = $this->properties->get();
    }
    
    protected function initContainer()
    {
        // container
        $proivder = new  \Tiny\DI\Definition\DefintionProivder([$this->properties]);
        $proivder->addDefinitionFromPath($this->properties['container.config_path']);
        $this->container = new Container($proivder);
        $this->container->set(self::class, $this);
        $this->container->set(Environment::class, $this->env);
        $this->container->set(Autoloader::class, $this->runtime->autoloader);
        $this->container->set(ExceptionHandler::class, $this->runtime->exceptionHandler);
        $this->container->set(RuntimeCachePool::class, $this->runtime->runtimeCachePool);
        $this->container->set('applicationCache', $this->runtime->getApplicationCache());
        $this->container->set(self::class, $this);
        $this->container->set(ApplicationBase::class, $this);
        $this->container->set(Properties::class, $this->properties);
    }
    
    /**
     * 初始化debug插件
     *
     * @return void
     */
    protected function _initPlugin()
    {
        if(!$this->properties['plugin']['enabled'])
        {
            return;
        }
        
        $plugins = (array)$this->properties['plugins'];
        if (key_exists('debug', $plugins))
        {
            array_unshift($plugins, $plugins['debug']);
            unset($plugins['debug']);
        }
        foreach($plugins as $pluginClass)
        {
            if (!class_exists($pluginClass))
            {
                throw new ApplicationException(sprintf('Plugin :%s is not exists!', $pluginClass));
            }
            $pluginInstance = new $pluginClass($this);
            $this->regPlugin($pluginInstance);
        }
    }
    
    /**
     * 初始化异常处理
     *
     * @return void
     */
    protected function _initException()
    {
        if ($this->properties['exception.enabled'])
        {
            $this->runtime->regExceptionHandler($this);
        }
    }
    
    /**
     * 初始化加载类库
     *
     * @return void
     */
    protected function _initImport()
    {
        $this->runtime->import($this->path, $this->_namespace);
        $prop = $this->properties['autoloader'];
        if (!is_array($prop['librarys']))
        {
            return;
        }
        if ($prop['no_realpath'])
        {
            foreach ($prop['librarys'] as $ns => $p)
            {
                $this->runtime->import($p, $ns);
            }
            return;
        }
        foreach ($prop['librarys'] as $ns => $p)
        {
            $this->runtime->import($this->properties[$p], $ns);
        }
    }
    
    /**
     * 初始化请求
     *
     * @return void
     */
    protected function _initRequest()
    {
        if (!$this->request)
        {
            return;
        }
        
        $this->request->setApplication($this);
        $this->request->setController($prop['controller']['default']);
        $this->request->setControllerParam($prop['controller']['param']);
        $this->request->setAction($prop['action']['default']);
        $this->request->setActionParam($prop['action']['param']);
    }
    
    /**
     * 初始化响应
     *
     */
    protected function _initResponse()
    {
        $this->response->setApplication($this);
        $this->response->setCharset($this->properties['charset']);
    }
    
    /**
     * 通过魔法函数触发插件的事件
     *
     *
     * @param string $method 函数名称
     * @param array $params 参数数组
     * @return void
     */
    protected function _onPlugin($method, $params)
    {
        $params[] = $this;
        foreach ($this->_plugins as $plugin)
        {
            call_user_func_array([$plugin, $method], $params);
        }
    }
    
    /**
     * 获取bootstrap实例 考虑到在application的初始化，不提供外部获取方式，避免错误使用。
     *
     * @throws ApplicationException
     * @return \Tiny\MVC\Bootstrap\Base
     */
    protected function _getBootstrap()
    {
        if ($this->_bootstrap)
        {
            return $this->_bootstrap;
        }
        if (!$this->_prop['bootstrap']['enabled'])
        {
            return FALSE;
        }
        $className = $this->_prop['bootstrap']['class'];
        if (!class_exists($className))
        {
            throw new ApplicationException(sprintf('bootstrap faild:%s 不存在', $className));
        }
        
        $this->_bootstrap = new $className();
        if (!$this->_bootstrap instanceof BootstrapBase)
        {
            throw new ApplicationException(sprintf('bootstrap faild:%s 没有继承\Tiny\Bootstrap\Base基类', $className));
        }
        return $this->_bootstrap;
    }
    
    /**
     * 引导
     *
     * @return void
     */
    protected function _bootstrap()
    {
        $bootstrap = $this->_getBootstrap();
        if ($bootstrap)
        {
            $bootstrap->bootstrap($this);
        }
    }
    
    /**
     * 执行路由
     *
     * @return void
     */
    protected function _route()
    {
        $prop = $this->_prop['router'];
        if (!$prop['enabled'])
        {
            return;
        }
        $routeString = $this->request->getRouterString();
        if (!$routeString || $routeString === '/')
        {
            return;
        }
        $routers = $prop['routers'] ?: [];
        $rules = $prop['rules'] ?: [];
        $router = $this->getRouter();
        
        foreach ($routers as $k => $r)
        {
            $router->regDriver($k, $r);
        }
        foreach ($rules as $rule)
        {
            $rule = (array)$rule;
            $router->addRule((array)$rule);
        }
        
        $ret = $router->route($routeString);
        if(!$ret)
        {
            throw new ApplicationException(sprintf('The RouterString[%s] does not match a router!', $routeString), E_NOFOUND);
        }
    }
    
    /**
     * 搜索模型
     *
     * @param string $modelName
     */
    protected function _searchModel($mname)
    {
        $modelFullName = $this->_getModelDataFromRuntimeCache($mname);
        if ($modelFullName)
        {
            return $modelFullName;
        }
        $mName = $mname;
        
        if (FALSE === strpos($mname, "\\"))
        {
            $mname = preg_replace('/([A-Z]+)/', '\\\\$1', ucfirst($mname));
        }
        $params = explode("\\", $mname);
        for ($i = count($params); $i > 0; $i--)
        {
            $modelFullName = join('\\', array_slice($params, 0, $i - 1)) . '\\' . join('', array_slice($params, $i - 1));
            if ($modelFullName[0] != '\\')
            {
                $modelFullName = "\\" . $modelFullName;
            }
            $modelFullName = "\\App\\Model" . $modelFullName;
            if (class_exists($modelFullName))
            {
                $this->_saveModelDataToRuntimeCache($mName, $modelFullName);
                return $modelFullName;
            }
        }
    }
}
?>