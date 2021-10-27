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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
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
use Tiny\Runtime\RuntimeCache;

// MVC下存放资源的文件夹
const TINYPHP_MVC_RESOURCES = __DIR__ . '/_resources/';

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
     * 应用层 运行时缓存KEY
     * @var array
     */
    const RUNTIME_CACHE_KEY = [
    'CONFIG' => 'app.config',
    'LANG' => 'app.lang',
    'MODEL' => 'app.model'
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
    protected $_cache;
    
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
     * 默认的命名空间
     *
     * @var string
     */
    protected $_namespace = '';
    
    /**
     * 控制器命名空间
     *
     * @var string
     */
    protected $_cNamespace;
    
    /**
     * 模型命名空间
     *
     * @var string
     */
    protected $_mNamespace;
    
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
     * @var RuntimeCache
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
    public function __construct($path, $profile = NULL)
    {
        /*runtime inited*/
        $this->runtime = Runtime::getInstance();
        $this->env = $this->runtime->env;
        if(!$this->runtime->getApplication())
        {
            $this->runtime->setApplication($this);
        }
     
        /*设置应用实例的运行时缓存*/
        $runtimeCache = $this->runtime->getApplicationCache();
        if ($runtimeCache)
        {
            $this->_runtimeCache = $runtimeCache;
        }
        
        /*应用实例路径配置和初始化*/
        $this->path = $path;
        if (!$profile)
        {
            $profile = $path . DIRECTORY_SEPARATOR . 'profile.php';
        }
        $this->profile = $profile;
        $this->_startTime = microtime(TRUE);
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
     * 设置配置实例
     *
     * @param Configuration $config 配置实例
     * @return ApplicationBase
     */
    public function setConfig(Configuration $config)
    {
        $this->_config = $config;
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
            $this->_router = new Router($this->request);
        }
        return $this->_router;
    }
    
    /**
     * 获取app实例的配置实例
     *
     * @return Configuration
     */
    public function getConfig()
    {
        if ($this->_config)
        {
            return $this->_config;
        }
        
        $prop = $this->_prop['config'];
        if (!$prop['enabled'])
        {
            throw new ApplicationException("properties.config.enabled is false!");
        }
        if (!$prop['path'])
        {
            throw new ApplicationException("properties.config.path is not allow null!");
        }
        $this->_config = new Configuration($prop['path']);
        if ($this->isDebug || !$prop['cache']['enabled'])
        {
            return $this->_config;
        }
        
        $data = $this->_getConfigDataFromRuntimeCache();
        if ($data && is_array($data))
        {
            $this->_config->setData($data);
        }
        else 
        {
            $data = $this->_config->get();
            $this->_saveConfigDataToRuntimeCache($data);
        }
        return $this->_config;
    }
    
    /**
     * 设置缓存实例
     *
     * @param Cache $cache 缓存实例
     * @return ApplicationBase
     */
    public function setCache(Cache $cache)
    {
        $this->_cache = $cache;
        return $this;
    }
    
    /**
     * 获取应用实例的缓存对象
     *
     * @return Cache
     */
    public function getCache()
    {
        if ($this->_cache)
        {
            return $this->_cache;
        }
        $prop = $this->_prop['cache'];
        if (!$prop['enabled'])
        {
            throw new ApplicationException("properties.cache.enabled is false!");
        }
        
        $this->_cache = Cache::getInstance();
        $prop['drivers'] = $prop['drivers'] ?: [];
        $prop['policys'] = $prop['policys'] ?: [];
        foreach ($prop['drivers'] as $type => $className)
        {
            Cache::regDriver($type, $className);
        }
        foreach ($prop['policys'] as $policy)
        {
            $policy['lifetime'] = $policy['lifetime'] ?: $prop['lifetime'];
            $policy['path'] = $policy['path'] ?: $prop['path'];
            $this->_cache->regPolicy($policy);
        }
        return $this->_cache;
    }
    
    /**
     * 设置数据池实例
     *
     * @param Data $data 数据池实例
     * @return ApplicationBase
     */
    public function setData(Data $data)
    {
        $this->_data = $data;
        return $this;
    }
    
    /**
     * 获取数据库连接池
     *
     * @return Data
     */
    public function getData()
    {
        if ($this->_data)
        {
            return $this->_data;
        }
        $prop = $this->_prop['data'];
        if (!$prop['enabled'])
        {
            throw new ApplicationException("properties.data.enabled is false!");
        }
        $this->_data = Data::getInstance();
        $prop['drivers'] = $prop['drivers'] ?: [];
        $prop['policys'] = $prop['policys'] ?: [];
        $prop['charset'] = $prop['charset'] ?: 'utf8';
        foreach ($prop['drivers'] as $type => $className)
        {
            Data::regDriver($type, $className);
        }
        foreach ($prop['policys'] as $policy)
        {
            $policy['def_charset'] = $prop['charset'];
            $this->_data->addPolicy($policy);
        }
        return $this->_data;
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
     * 设置语言包实例
     *
     * @param Lang $lang 语言包实例
     * @return self
     */
    public function setLang(Lang $lang)
    {
        $this->_lang = $lang;
        return $this;
    }
    
    /**
     * 获取语言操作对象
     *
     * @param void
     * @return Lang
     */
    public function getLang()
    {
        if ($this->_lang)
        {
            return $this->_lang;
        }
        $prop = $this->_prop['lang'];
        if (!$prop['enabled'])
        {
            throw new ApplicationException("properties.lang.enabled is false!");
        }
        
        $this->_lang = Lang::getInstance();
        $this->_lang->setLocale($prop['locale'])->setPath($prop['path']);
        if ($this->isDebug || !$prop['cache']['enabled'])
        {
            return $this->_lang;
        }
        $data = $this->_getLangDataFromRuntimeCache();
        if ($data && is_array($data))
        {
            $this->_lang->setData($data);
        }
        else
        {
            $data = $this->_lang->getData();
            $this->_saveLangDataToRuntimeCache($data);
        }
        return $this->_lang;
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
        $controllerName = $this->_cNamespace . $cparam;
        if (!class_exists($controllerName))
        {
            throw new ApplicationException("Dispatch errror:controller,{$controllerName}不存在，无法加载", E_ERROR);
        }
        
        $controllerInstance = new $controllerName();
        if (!$controllerInstance instanceof \Tiny\MVC\Controller\Base)
        {
            throw new ApplicationException("Controller:'{$controllerName}' is not instanceof Tiny\MVC\Controlller\Controller!", E_ERROR);
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
     * 设置视图实例
     *
     * @param View $viewer 视图实例
     * @return Base
     */
    public function setView(View $view)
    {
        $this->_view = $view;
        return $this;
    }
    
    /**
     * 获取视图类型
     *
     * @return View
     */
    public function getView()
    {
        if ($this->_view)
        {
            return $this->_view;
        }
        $prop = $this->_prop['view'];
        $this->_view = View::getInstance();
        $this->_view->setApplication($this);
        $assign = $prop['assign'] ?: [];
        
        
        
        $assign['env'] = $this->runtime->env;
        $assign['request'] = $this->request;
        $assign['response'] = $this->response;
        
        if ($this->_prop['config']['enabled'])
        {
            $assign['config'] = $this->getConfig();
        }
        
        if ($this->_prop['lang']['enabled'])
        {
            $assign['lang'] = $this->getLang();
            if ($this->_prop['view']['lang']['enabled'] !== FALSE)
            {
                $srcLocale = $prop['src'] . $this->_prop['lang']['locale'] . DIRECTORY_SEPARATOR;
                $prop['src'] = [$prop['src'], $srcLocale];
            }
        }
        $this->_view->setTemplateDir($prop['src']);
        if ($prop['cache'] && $prop['cache']['enabled'])
        {
            $this->_view->setCache($prop['cache']['dir'], (int)$prop['cache']['lifetime']);
        }
        
        // engine初始化
        foreach ((array)$prop['engines'] as $econfig)
        {
            $this->_view->bindEngine($econfig);
        }
        
        //helper初始化
        foreach ((array)$prop['helpers'] as $econfig)
        {
            $this->_view->bindEngine($econfig);
        }
        
        $this->_view->setCompileDir($prop['compile']);
        
        $this->_view->assign($assign);
        return $this->_view;
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
        //$this->_doFilter();
        $this->onPreDispatch();
        $this->dispatch();
        $this->onPostDispatch();
        $this->response->output();
    }
    
    /**
     * 分发
     *
     * @access protected
     * @param string $cname 控制器名称
     * @param string $aname 动作名称
     * @return mixed
     */
    public function dispatch(string $cname = NULL, string $aname = NULL, array $args = [], bool $isEvent = FALSE)
    {
        // 获取控制器实例
        $controller = $this->getController($cname);
        $this->controller = $controller;
        
        // 获取执行动作名称
        $action = $this->getAction($aname, $isEvent);
        
        // 触发事件
        if ($isEvent)
        {
            if (method_exists($controller, $action))
            {
                return call_user_func_array([
                    $controller,
                    $action
                ], $args);
            }
            return FALSE;
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
            throw new ApplicationException("Dispatch error: The Action '{$aname}' of Controller '{$cname}' is not exists ");
        }
        $ret = call_user_func_array([$controller, $action], $args);
        call_user_func_array([$controller,'onEndExecute'], $args);
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
        $this->_initResponse();
        $this->_initProperties();
        $this->_initNamespace();
        $this->_initPlugin();
        $this->_initImport();
        $this->_initException();
        $this->_initRequest();
    }
    
    /**
     * 初始化应用程序的配置对象
     *
     * @return void
     */
    protected function _initProperties()
    {
        $this->properties = new Configuration($this->profile);
        if ($this->properties['debug']['enabled'])
        {
            $this->isDebug = TRUE;
        }
        $this->_initPath($this->properties['path']);
        
        $prop = $this->properties->get();
        $this->_namespace = $prop['app']['namespace'];
        $this->setTimezone($prop['timezone']);
        $this->charset = (string)$prop['charset'] ?: 'zh_cn';
        $this->_prop = $prop;
    }
    
    /**
     * 初始化命名空间
     *
     * @return void
     */
    protected function _initNamespace()
    {
        $this->_namespace = $this->_prop['app']['namespace'] ?: 'App';
        $cprefix = $this->_prop['controller']['namespace'];
        if (static::class == 'Tiny\MVC\ConsoleApplication')
        {
            $cprefix = $this->_prop['controller']['console'];
        }
        elseif (static::class == 'Tiny\MVC\RPCApplication')
        {
            $cprefix = $this->_prop['controller']['rpc'];
        }
        
        $this->_cNamespace = '\\' . $this->_namespace . '\\' . $cprefix;
        $this->_mNamespace = '\\' . $this->_namespace . '\\' . $this->_prop['model']['namespace'];
    }
    
    /**
     * 初始化debug插件
     *
     * @return void
     */
    protected function _initPlugin()
    {
        if ($this->properties['debug']['enabled'])
        {
            $this->_debug = new \Tiny\MVC\Plugin\Debug($this);
            $this->regPlugin($this->_debug);
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
     * 初始化路径
     *
     * @param array $paths 初始化路径
     * @return void
     *
     */
    protected function _initPath(array $paths)
    {
        $runtimePath = $this->properties['app.runtime'];
        if (!$runtimePath)
        {
            $runtimePath = $this->path . 'runtime/';
        }
        if ($runtimePath && 0 === strpos($runtimePath, 'runtime'))
        {
            $runtimePath = $this->path . $runtimePath;
        }
        foreach ($paths as $p)
        {
            $path = $this->properties[$p];
            if (!$path)
            {
                continue;
            }
            if (0 === strpos($path, 'runtime'))
            {
                $rpath = preg_replace("/\/+/", "/", $runtimePath . substr($path, 7));
                if (!file_exists($rpath))
                {
                    mkdir($rpath, 0777, TRUE);
                }
                $this->properties[$p] = $rpath;
                continue;
            }
            $this->properties[$p] = $this->path . $path;
        }
    }
    
    /**
     * 初始化加载类库
     *
     * @return void
     */
    protected function _initImport()
    {
        $runtime = Runtime::getInstance();
        $runtime->import($this->path, $this->_namespace);
        $prop = $this->_prop['autoloader'];
        if (!is_array($prop['librarys']))
        {
            return;
        }
        if ($prop['no_realpath'])
        {
            foreach ($prop['librarys'] as $ns => $p)
            {
                $runtime->import($p, $ns);
            }
            return;
        }
        foreach ($prop['librarys'] as $ns => $p)
        {
            $runtime->import($this->properties[$p], $ns);
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
        $prop = $this->_prop;
        $this->request->setController($prop['controller']['default']);
        $this->request->setControllerParam($prop['controller']['param']);
        $this->request->setAction($prop['action']['default']);
        $this->request->setActionParam($prop['action']['param']);
    }
    
    /**
     * 初始化响应
     *
     * @return void
     */
    protected function _initResponse()
    {
        $this->response->setApplication($this);
        $this->response->setLocale($this->properties['lang']['locale']);
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
        foreach ($this->_plugins as $plugin)
        {
            $params[] = $this;
            call_user_func_array([
                $plugin,
                $method
            ], $params);
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
        $routers = $prop['routers'] ?: [];
        $rules = $prop['rules'] ?: [];
        $router = $this->getRouter();
        
        foreach ($routers as $k => $r)
        {
            $router->regDriver($k, $r);
        }
        foreach ($rules as $rule)
        {
            $router->addRule((array)$rule);
        }
        $router->route();
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
            $modelFullName = $this->_mNamespace . $modelFullName;
            if (class_exists($modelFullName))
            {
                $this->_saveModelDataToRuntimeCache($mName, $modelFullName);
                return $modelFullName;
            }
        }
    }
    
    /**
     * 从运行时缓存获取模型类查找配置数据
     *
     * @return data|FALSE
     */
    protected function _getModelDataFromRuntimeCache($mname)
    {
        if (FALSE === $this->_modelSearchNodes)
        {
            $this->_modelSearchNodes = $this->_getDataFromRuntimeCache(self::RUNTIME_CACHE_KEY['MODEL']) ?: [];
        }
        if($this->_modelSearchNodes[$mname])
        {
            return $this->_modelSearchNodes[$mname];
        }
    }
    
    /**
     * 保存模型类配置数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function _saveModelDataToRuntimeCache($mname, $modelFullName)
    {
        $this->_modelSearchNodes[$mname] = $modelFullName;
        return $this->_saveDataToRuntimeCache(self::RUNTIME_CACHE_KEY['MODEL'], $this->_modelSearchNodes);
    }
    
    /**
     * 从运行时缓存获取语言包配置数据
     *
     * @return data|FALSE
     */
    protected function _getLangDataFromRuntimeCache()
    {
        return $this->_getDataFromRuntimeCache(self::RUNTIME_CACHE_KEY['LANG']);
    }
    
    /**
     * 保存语言包配置数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function _saveLangDataToRuntimeCache($data)
    {
        return $this->_saveDataToRuntimeCache(self::RUNTIME_CACHE_KEY['LANG'], $data);
    }
    
    /**
     * 从运行时缓存获取配置数据
     *
     * @return data|FALSE
     */
    protected function _getConfigDataFromRuntimeCache()
    {
        return $this->_getDataFromRuntimeCache(self::RUNTIME_CACHE_KEY['CONFIG']);
    }
    
    /**
     * 保存配置数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function _saveConfigDataToRuntimeCache($data)
    {
        return $this->_saveDataToRuntimeCache(self::RUNTIME_CACHE_KEY['CONFIG'], $data);
    }
    
    /**
     * 从运行时缓存获取数据
     * 
     * @return data|FALSE
     */
    protected function _getDataFromRuntimeCache($key)
    {
        if (!$this->_runtimeCache)
        {
            return FALSE;
        }
        $data = $this->_runtimeCache->get($key);
        if (!$data || !is_array($data))
        {
            return FALSE;
        }
        return $data;
    }
    
    /**
     * 保存数据到运行时缓存
     * 
     * @param array $data
     * @return boolean
     */
    protected function _saveDataToRuntimeCache($key, $data)
    {
        if (!$this->_runtimeCache)
        {
            return FALSE;
        }
        return $this->_runtimeCache->set($key, $data);
    }
}
?>