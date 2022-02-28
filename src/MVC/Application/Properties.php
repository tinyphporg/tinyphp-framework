<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Properties.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:18:09
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:18:09 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Application;

use Tiny\Config\Configuration;
use Tiny\DI\ContainerInterface;
use Tiny\Data\Data;
use Tiny\Lang\Lang;
use Tiny\MVC\View\View;
use Tiny\Tiny;
use Tiny\Runtime\Runtime;
use Tiny\MVC\Router\Router;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\Definition\CallableDefinition;
use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Request\ConsoleRequest;
use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;
use Tiny\MVC\Response\ConsoleResponse;
use Tiny\MVC\Response\WebResponse;
use Tiny\MVC\Controller\Dispatcher;
use Tiny\Filter\Filter;
use Tiny\MVC\Request\Param\Get;
use Tiny\MVC\Request\Param\Post;
use Tiny\Log\Logger;
use Tiny\Cache\Cache;
use Tiny\Cache\CacheInterface;
use Tiny\DI\Definition\Provider\DefinitionProviderInterface;
use Tiny\DI\Definition\Provider\DefinitionProvider;
use Tiny\Cache\Storager\PHP;
use Tiny\MVC\Web\HttpSession;
use Tiny\MVC\Web\HttpCookie;
use Tiny\Data\Db\Db;

/**
 * application属性
 *
 * @package Tiny.MVC.Application
 * @since 2021年11月27日 下午1:01:32
 * @final 2021年11月27日下午1:01:32
 */
class Properties extends Configuration implements DefinitionProviderInterface
{
    
    protected $app;
    
    /**
     * application的命名空间
     *
     * @var String
     */
    protected $namespace = 'App';
    
    /**
     * 控制器的命名空间
     *
     * @var string
     */
    protected $controllerNamespace = 'App\Controller';
    
    /**
     * 模型的命名空间
     *
     * @var string
     */
    protected $modelNamespace = 'App\Model';
    
    /**
     * 源定义
     *
     * @var array
     */
    protected $sourceDefinitions = [];
    
    /**
     * 类别名
     *
     * @var array
     */
    protected $classAlias = [
        'lang' => Lang::class,
        'view' => View::class,
        CacheInterface::class => Cache::class,
    ];
    
    /**
     * 构造函数
     *
     * @param ApplicationBase $app
     * @param string|array $cpath 配置文件路径
     */
    public function __construct(ApplicationBase $app, $cpath)
    {
        parent::__construct($cpath);
        $this->app = $app;
        $this->init($app);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinition()
     */
    public function getDefinition(string $name)
    {
        switch ($name) {
            case Router::class:
                return $this->getRouterDefinition();
            case Dispatcher::class:
                return $this->getDispatcherDefinition();
            case Cache::class:
                return $this->getCacheDefinition();
            case Data::class:
                return $this->getDataDefinition();
            case Configuration::class:
                return $this->getConfigDefinition();
            case Lang::class:
                return $this->getLangDefinition();
            case View::class:
                return $this->getViewDefinition();
            case Filter::class:
                return $this->getFilterDefinition();
            case Logger::class:
                return $this->getLoggerDefinition();
            case HttpSession::class:
                return $this->getSessionDefinition();
            case HttpCookie::class:
                return $this->getCookieDefinition();
        }
        
        // 匹配请求
        if ($definition = $this->getRequestDefinition($name)) {
            return $definition;
        }
        
        // 匹配控制器
        if ($definition = $this->getControllerDefinition($name)) {
            return $definition;
        }
        
        // 匹配模型
        if ($definition = $this->getModelDefinition($name)) {
            return $definition;
        }
        
        // 匹配缓存
        if ($definition = $this->getCacheStoragerDefinition($name)) {
            return $definition;
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinitions()
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }
    
    /**
     * 初始化application配置
     *
     * @param ApplicationBase $app
     */
    protected function init(ApplicationBase $app)
    {
        $this->initDebug();
        $this->initNamespace();
        $this->initPath();
        $this->initAutoloader();
        $this->initDefinitions();
        $this->initInConsoleApplication();
    }
    
    /**
     * 初始化debug
     */
    protected function initDebug()
    {
        $config = $this['debug'];
        if (!$config['enabled'] || !$config['eventListener']) {
            return;
        }
        $this->app->isDebug = true;
        $this['event.listeners.debug'] = $config['eventListener'];
    }
    
    /**
     * 初始化命名空间
     */
    protected function initNamespace()
    {
        
        // timezone
        $timezone = $this['timezone'] ?: 'PRC';
        if ($timezone !== date_default_timezone_get()) {
            date_default_timezone_set($timezone);
        }
        
        // app namespace
        $this->namespace = (string)$this['app.namespace'] ?: $this->namespace;
        
        // controller namespace
        $cnamespace = $this['controller.namespace'];
        $controllerNamespace = ($this->app instanceof ConsoleApplication) ? (string)$cnamespace['console'] : (string)$cnamespace['default'];
        if ($controllerNamespace) {
            $this->controllerNamespace = $this->namespace . '\\' . $controllerNamespace;
        }
        
        // model namespace
        $modelNameSpace = (string)$this['model.namespace'];
        if ($modelNameSpace) {
            $this->modelNameSpace = $this->namespace . '\\' . $modelNameSpace;
        }
    }
    
    /**
     * 初始化配置路径
     */
    protected function initPath()
    {
        $appPath = $this->app->path;
        $paths = $this->get('path');
        $parsedPaths = [
            'app' => $this->app->path
        ];
        foreach ($paths as $p) {
            $path = $this->get($p);
            if (!$path) {
                continue;
            }
            $rpath = preg_replace_callback("/{([a-z][a-z0-9_]+)}/is", function ($matchs) use ($parsedPaths) {
                $pathName = $matchs[1];
                if (!key_exists($pathName, $parsedPaths) && key_exists('src.' . $pathName, $parsedPaths)) {
                    $pathName = 'src.' . $pathName;
                }
                return key_exists($pathName, $parsedPaths) ? $parsedPaths[$pathName] : '';
            }, $path);
            if ($rpath['0'] !== '/') {
                $rpath = $appPath . $rpath;
            }
           
            $rpath = $this->getAbsolutePath($rpath);
            $parsedPaths[$p] = $rpath;
            $this->set($p, $rpath);
        }
    }
    
    /**
     * 获取绝对路径
     *
     * @param string $path
     * @return string
     */
    protected function getAbsolutePath($path)
    {
        $pathstart = '';
        if (strpos($path, "phar://") !== false) {
            $path = substr($path, 7);
            $pathstart = 'phar://';
        }
        
        $path = str_replace(['/','\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part)
                continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $path = $pathstart . (($path[0] == DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '') . implode(DIRECTORY_SEPARATOR, $absolutes);
        if (substr($path, -4) !== '.php') {
            $path .= DIRECTORY_SEPARATOR;
        }
       return $path;
    }
    
    /**
     * 初始化加载类库
     */
    protected function initAutoloader()
    {
        $runtime = $this->app->get(Runtime::class);
        
        // app
        $namespace = (string)$this['app.namespace'] ?: 'App';
        $runtime->addToNamespacePathMap($namespace, $this->app->path);
        $isRealpath = (bool)$this['autoloader.is_realpath'];
        
        // namespaces
        $namespaces = (array)$this['autoloader.namespaces'];
        foreach ($namespaces as $ns => $p) {
            $path = $isRealpath ? $p : $this[$p];
            $this['autoloader.namespaces.' . $ns] = $path;
            $runtime->addToNamespacePathMap($ns, $path);
        }
        // classes
        $classes = (array)$this['autoloader.classes'];
        foreach ($classes as $class => $p) {
            $path = $isRealpath ? $p : $this[$p];
            $runtime->addToClassPathMap($class, $path);
            $this['autoloader.classes.' . $class] = $path;
        }
    }
    
    /**
     * 初始化定义源
     */
    protected function initDefinitions()
    {
        $sourceDefinitions = $this->sourceDefinitions;
        
        // Request
        $requestClassName = ($this->app instanceof ConsoleApplication) ? ConsoleRequest::class : WebRequest::class;
        
        // Request definition
        $sourceDefinitions[] = $requestClassName;
        $sourceDefinitions[Request::class] = function (ContainerInterface $container) use ($requestClassName) {
            $request = $container->get($requestClassName);
            
            if (!$request instanceof Request) {
                throw new \Exception('aaa');
            }
            
            // controller
            $request->setControllerName($this['controller.default']);
            $request->setControllerParamName($this['controller.param']);
            
            // action
            $request->setActionName($this['action.default']);
            $request->setActionParamName($this['action.param']);
            return $request;
        };
        
        // Response
        $responseClassName = ($this->app instanceof ConsoleApplication) ? ConsoleResponse::class : WebResponse::class;
        
        // response definition
        $sourceDefinitions[] = $responseClassName;
        $sourceDefinitions[Response::class] = function (ContainerInterface $container) use ($responseClassName) {
            $response = $container->get($responseClassName);
            
            if (!$response instanceof Response) {
                throw new ApplicationException('Instantiation failed: %s must implement %s', $responseClassName, Response::class);
            }
            
            // output charset
            $response->setCharset($this['charset']);
            return $response;
        };
        
        // 别名
        $sourceDefinitions['alias'] = $this->classAlias;
        
        // definition proivder
        $proivder = $this->app->container->get(DefinitionProvider::class);
        $proivder->addDefinitionFromArray($sourceDefinitions);
        $containerPath = $this['container.config_path'];
        if ($containerPath) {
            $proivder->addDefinitionFromPath($containerPath);
        }
        $proivder->addDefinitionProivder($this);
    }
    
    /**
     * 应用于命令行时的初始化
     */
    protected function initInConsoleApplication()
    {
        if (!$this->app instanceof ConsoleApplication) {
            return;
        }
        $this->initBuilder();
        $this->initDaemon();
        $this->initUIInstaller();
    }
    
    /**
     * 初始化命令行下的打包机制
     */
    protected function initBuilder()
    {
        $config = $this['builder'];
        if (!$config || !$config['enabled'] || !$config['eventListener']) {
            return;
        }
        $this['event.listeners.builder'] = $config['eventListener'];
    }
    
    /**
     * 初始化服务守护进程
     */
    protected function initDaemon()
    {
        $config = $this['daemon'];
        if (!$config || !$config['enabled'] || !$config['eventListener']) {
            return;
        }
        $this['event.listeners.daemon'] = $config['eventListener'];
    }
    
    /**
     * 初始化tinyphp-ui的前端库同步
     */
    protected function initUIInstaller()
    {
        $config = $this['view.ui'];
        if (!$config || !$config['enabled']) {
            return;
        }
        $installConfig = (array)$config['installer'];
        if (!$installConfig || !$installConfig['eventListener']) {
            return;
        }
        $this['event.listeners.uiinstaller'] = (string)$installConfig['eventListener'];
    }
    
    /**
     * 获取request内的数据
     *
     * @param string $name
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getRequestDefinition($name)
    {
        if (in_array($name, [
            Get::class,
            Post::class,
            \Tiny\Runtime\Param\Param::class
        ])) {
            return new CallableDefinition($name, function (Request $request) use ($name) {
                switch ($name) {
                    case Get::class:
                        return $request->get;
                    case Post::class:
                        return $request->post;
                    case \Tiny\Runtime\Param\Param::class:
                        return $request->param;
                }
            });
        }
    }
    
    /**
     * 获取cache存储器的定义
     *
     * @param string $name
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getCacheStoragerDefinition(string $name)
    {
        $config = $this['cache'];
        if (!$config['enabled'] || !is_array($config['config'])) {
            return;
        }
        
        if (!$storagerId = Cache::getStoragerId($name)) {
            return;
        }
        
        //
        $caches = $config['config'];
        $cacheIndex = array_search($storagerId, array_column($caches, 'storager'), true);
        if (false === $cacheIndex) {
            return;
        }
        if (!$cacheId = $caches[$cacheIndex]['id']) {
            return;
        }
        
        return new CallableDefinition($name, function (Cache $cache) use ($cacheId) {
            return $cache[$cacheId];
        });
    }
    
    /**
     * 获取控制器的模型定义
     *
     * @param string $name
     * @return \Tiny\DI\Definition\ObjectDefinition|boolean
     */
    protected function getControllerDefinition(string $name)
    {
        if (strpos($name, $this->controllerNamespace) === 0) {
            return new ObjectDefinition($name, $name);
        }
        return false;
    }
    
    /**
     * 获取模型的容器定义
     *
     * @param string $name
     * @return \Tiny\DI\Definition\ObjectDefinition|boolean
     */
    protected function getModelDefinition(string $name)
    {
        if (strpos($name, $this->modelNamespace) === 0) {
            return new ObjectDefinition($name, $name);
        }
        return false;
    }
    
    /**
     * 获取配置的容器定义
     *
     * @throws ApplicationException
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getConfigDefinition()
    {
        if (!$this['config.enabled']) {
            return;
        }
        
        return new CallableDefinition(Configuration::class, 
        function (ContainerInterface $container) {
            $config = $this['config'];
            if (!$config['path']) {
                throw new ApplicationException("properties.config.path is not allow null!");
            }
            
            $configInstance = new Configuration($config['path']);
            if (!$config['cache']['enabled']) {
                return $configInstance;
            }
            
            // config cache
            $cacheInstance = $container->get(PHP::class);
            $cacheKey = (string)$config['cache']['key'] ?: 'application:cache:config';
            $configData = (array)$cacheInstance->get($cacheKey);
            if ($configData) {
                $configInstance->setData($configData);
            } else {
                $configData = $configInstance->get();
                $cacheInstance->set($cacheKey, $configData);
            }
            return $configInstance;
        });
    }
    
    /**
     * 获取缓存的定义
     *
     * @return CallableDefinition
     */
    protected function getCacheDefinition()
    {
        if (!$this['cache.enabled']) {
            return;
        }
        
        return new CallableDefinition(Cache::class, function () {
            $config = $this['cache'];
            
            // 存储器映射
            $storagers = (array)$config['storagers'];
            foreach ($storagers as $storagerId => $storagerClass) {
                Cache::regStorager($storagerId, $storagerClass);
            }
            
            $defaultId = (string)$config['default_id'];
            $ttl = (int)$config['ttl'];
            $path = (string)$config['path'];
            
            $cacheInstance = new Cache();
            $cacheInstance->setDefaultPath($path);
            $cacheInstance->setDefaultId($defaultId);
            $cacheInstance->setDefaultTtl($ttl);
            
            $caches = (array)$config['config'];
            $phpCacheId = false;
            foreach ($caches as $cacheConfig) {
                if ('php' === $cacheConfig['storager']) {
                    $phpCacheId = $cacheConfig['id'];
                }
                $cacheInstance->addStorager($cacheConfig['id'], $cacheConfig['storager'], $cacheConfig['options']);
            }
            if (!$phpCacheId) {
                $phpCacheId = 'application.cache';
                $cacheInstance->addStorager($phpCacheId, 'php', []);
            }
            return $cacheInstance;
        });
    }
    
    /**
     * 获取HttpCookie的实例定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getCookieDefinition()
    {
        return new CallableDefinition(HttpCookie::class, function () {
            $config = $this['cookie'];
            $config['data'] = $_COOKIE;
            return new HttpCookie($config);
        });
    }
    
    /**
     * 获取数据操作池的定义
     *
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getDataDefinition()
    {
        if (!$this['data.enabled']) {
            return;
        }
                
        return new CallableDefinition(Data::class, function (ApplicationBase $app) {
            $config = $this['data'];
            
            // 驱动
            $dirvers = $config['sources'] ?: [];
            foreach ($dirvers as $id => $className) {
                Data::regDataSourceDriver($id, $className);
            }
            
            // 数据源配置
            $sources = (array)$config['sources'] ?: [];
            
            // 编码
            $charset = $config['charset'] ?: 'utf8';
            
            // 实例化开始
            $dataPool = new Data();
            
            // 添加数据源
            foreach ($sources as $sourceConfig) {
                $sourceConfig['def_charset'] = $charset;
                $sourceConfig['is_record'] = (bool)$app->isDebug;
                $dataPool->addDataSource($sourceConfig);
            }
            return $dataPool;
        });
    }
    
    protected function getFilterDefinition()
    {
        if (!$this['filter.enabled']) {
            return false;
        }
        
        return new CallableDefinition(Filter::class, function (ApplicationBase $app) {
            $prop = $this['filter'];
            $filterInstance = new Filter();
            
            $filterClass = ($app instanceof WebApplication) ? $prop['web'] : $prop['console'];
            if ($filterClass && is_array($filterClass)) {
                foreach ($filterClass as $fclass) {
                    $filterInstance->addFilter($fclass);
                }
            }
            if ($filterClass && is_string($filterClass)) {
                $filterInstance->addFilter($filterClass);
            }
            foreach ((array)$prop['filters'] as $fname) {
                $filterInstance->addFilter($fname);
            }
            return $filterInstance;
        });
    }
    
    /**
     * 获取语言包容器定义
     *
     * @return boolean|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getLangDefinition()
    {
        if (!$this['lang.enabled']) {
            return false;
        }
        
        return new CallableDefinition(Lang::class, function (ContainerInterface $container) {
            
            $config = (array)$this['lang'];
            $locale = (string)$config['locale'];
            $configPath = (string)$config['path'];
            
            // create
            $langInstance = new Lang();
            $langInstance->setLocale($locale);
            $langInstance->setPath($configPath);
            
            if (!$config['cache']['enabled']) {
                return $langInstance;
            }
            
            // config cache
            $cacheInstance = $container->get(PHP::class);
            $cacheKey = (string)$config['cache']['key'] ?: 'application:cache:lang';
            $langData = (array)$cacheInstance->get($cacheKey);
            if ($langData) {
                $langInstance->setData($langData);
            } else {
                $langData = $langInstance->getData();
                $cacheInstance->set($cacheKey, $langData);
            }
            return $langInstance;
        });
    }
    
    /**
     * 获取日志生成器的容器定义
     *
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getLoggerDefinition()
    {
        if (!$this['log.enabled']) {
            return;
        }
        
        return new CallableDefinition(Logger::class, function () {
            $logger = new Logger();
            
            $logConfig = $this['log'];
            $drivers = (array)$logConfig['drivers'] ?: [];
            foreach ($drivers as $type => $className) {
                Logger::regLogWriter($type, $className);
            }
            
            $writerConfig = ('file' === $logConfig['writer']) ? [
                'path' => $logConfig['path'],
            ] : [];
            
            $logger->addLogWriter($logConfig['writer'], $writerConfig);
            return $logger;
        });
    }
    
    /**
     * 获取路由的容器定义
     *
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getRouterDefinition()
    {
        if (!$this['router.enabled']) {
            return;
        }
        
        return new CallableDefinition(Router::class, function (Request $request) {
            
            // router config
            $routerConfig = $this['router'];
            $routerConfig['routes'] = (array)$routerConfig['routers'];
            $routerConfig['rules'] = (array)$routerConfig['rules'];
            
            $routerInstance = new Router($request);
            
            // 注册路由
            foreach ($routerConfig['routes'] as $routerName => $routerclass) {
                $routerInstance->addRoute($routerName, $routerclass);
            }
            
            // 注册路由规则
            foreach ($routerConfig['rules'] as $rule) {
                $rule = (array)$rule;
                $routerInstance->addRouteRule($rule);
            }
            return $routerInstance;
        });
    }
    
    /**
     * 获取派发器的容器定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getDispatcherDefinition()
    {
        return new CallableDefinition(Router::class, function (ContainerInterface $container) {
            $dispatcher = new Dispatcher($container);
            
            // controller
            $dispatcher->setControllerNamespace($this->controllerNamespace);
            
            // action
            if ($this['action.suffix']) {
                $dispatcher->setActionSuffix($this['action.suffix']);
            }
            return $dispatcher;
        });
    }
    
    /**
     * 获取HttpSession 的实例定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getSessionDefinition()
    {
        return new CallableDefinition(HttpSession::class, function () {
            $config = $this['session'];
            return new HttpSession($config);
        });
    }
    
    /**
     * 视图定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getViewDefinition()
    {
        return new CallableDefinition(View::class,
            function (ContainerInterface $container) {
                
                $config = $this['view'];
                
                $viewInstance = new View($this->app);
                
                $helpers = (array)$config['helpers'];
                $engines = (array)$config['engines'];
                
                $assign = (array)$config['assign'] ?: [];
                
                if ($this['config.enabled']) {
                    $assign['config'] = $container->get(Configuration::class);
                }
                
                $defaultTemplateDirname = TINY_FRAMEWORK_RESOURCE_PATH . 'mvc/view/';
                $templateDirs = [
                    $defaultTemplateDirname
                ];
                $templateDirname = $config['template_dirname'] ?: 'default';
                $templateDirs[] = $config['src'] . $templateDirname . DIRECTORY_SEPARATOR;
                
                // composer require tinyphp-ui;
                $uiconfig = $config['ui'];
                if ($uiconfig['enabled']) {
                    $uiHelperName = (string)$uiconfig['helper'];
                    if ($uiHelperName) {
                        $helpers[] = [
                            'helper' => $uiHelperName
                        ];
                    }
                    
                    $templatePlugin = (string)$uiconfig['template_plugin'];
                    if ($templatePlugin) {
                        $uiPluginConfig = [
                            'public_path' => $config['ui']['public_path'],
                            'inject' => $config['ui']['inject'],
                            'dev_enabled' => $config['ui']['dev_enabled'],
                            'dev_public_path' => $config['ui']['dev_public_path']
                        ];
                        $engines[] = [
                            'engine' => \Tiny\MVC\View\Engine\Template::class,
                            'config' => [
                                'plugins' => [
                                    [
                                        'plugin' => $templatePlugin,
                                        'config' => $uiPluginConfig
                                    ]
                                ]
                            ]
                        ];
                    }
                    if ($uiconfig['template_dirname']) {
                        $templateDirs[] = (string)$uiconfig['template_dirname'];
                    }
                }
                
                if ($this['lang.enabled']) {
                    $assign['lang'] = $container->get('lang');
                    if ($config['view']['lang']['enabled'] !== false) {
                        $templateDirs[] = $config['src'] . $this['lang.locale'] . DIRECTORY_SEPARATOR;
                    }
                }
                
                // 设置模板搜索目录
                
                $templateDirs = array_reverse($templateDirs);
                $viewInstance->setTemplateDir($templateDirs);
                if ($config['cache'] && $config['cache']['enabled']) {
                    $viewInstance->setCache($config['cache']['dir'], (int)$config['cache']['lifetime']);
                }
                
                // engine初始化
                foreach ($engines as $econfig) {
                    $viewInstance->bindEngine($econfig);
                }
                
                // helper初始化
                foreach ($helpers as $econfig) {
                    $viewInstance->bindHelper($econfig);
                }
                
                $viewInstance->setCompileDir($config['compile']);
                $viewInstance->assign($assign);
                return $viewInstance;
            });
    }
}
?>