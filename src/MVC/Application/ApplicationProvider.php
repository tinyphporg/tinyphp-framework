<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ApplicationProvider.php
 * @author King
 * @version stable 2.0
 * @Date 2022年5月17日下午11:40:22
 * @Class List class
 * @Function List function_container
 * @History King 2022年5月17日下午11:40:22 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Application;

use Tiny\DI\Definition\Provider\DefinitionProviderInterface;
use Tiny\Lang\Lang;
use Tiny\MVC\View\View;
use Tiny\Cache\Cache;
use Tiny\Cache\CacheInterface;
use Tiny\Cache\Storager\SingleCache;
use Tiny\DI\Definition\Provider\DefinitionProvider;
use Tiny\MVC\Request\ConsoleRequest;
use Tiny\MVC\Request\Request;
use Tiny\DI\ContainerInterface;
use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Response\ConsoleResponse;
use Tiny\MVC\Response\WebResponse;
use Tiny\DI\Definition\InstanceDefinition;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\MVC\Response\Response;
use Tiny\DI\Definition\CallableDefinition;
use Tiny\MVC\Router\Router;
use Tiny\MVC\Controller\Dispatcher;
use Tiny\Config\Configuration;
use Tiny\Data\Data;
use Tiny\Filter\Filter;
use Tiny\MVC\Request\Param\Get;
use Tiny\MVC\Request\Param\Post;
use Tiny\Runtime\Param\Param;
use Tiny\Log\Logger;
use Tiny\MVC\Web\HttpSession;
use Tiny\MVC\Web\HttpCookie;
use Tiny\Event\EventManager;
use Tiny\MVC\View\Engine\StaticFile;

/**
* 应用的容器提供源 必须开启
* 
* @package Tiny.MVC.Application
* 
* @since 2022年5月22日下午3:11:51
* @final 2022年5月22日下午3:11:51
*/
class ApplicationProvider implements DefinitionProviderInterface
{
    
    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * 当前应用属性实例
     *
     * @var Properties
     */
    protected $properties;
    
    /**
     * 源定义数组
     *
     * @var array
     */
    protected $sourceDefinitions = [];
    
    /**
     * 是否加载了application的定义源
     *
     * @var boolean|array
     */
    protected $sourceFiles = false;
    
    /**
     * 类别名
     *
     * @var array
     */
    protected $alias = [
        'app.properties' => Properties::class,
        'app.config' => Configuration::class,
        'app.lang' => Lang::class,
        'app.view' => View::class,
        'app.application.cache' => ApplicationCache::class,
        'app.cache' => Cache::class,
        'app.request' => Request::class,
        CacheInterface::class => Cache::class,
        'app.router' => Router::class,
        'app.dispatcher' => Dispatcher::class,
        'app.request' => Request::class,
        'app.response' => Response::class,
        'app.cookie' => HttpCookie::class,
        'app.session' => HttpSession::class,
        'app.eventmanager' => EventManager::class,
        'app.logger' => Logger::class,
        'app.data' => Data::class
    ];
    
    /**
     * 构造函数
     *
     * @param DefinitionProvider $provider 容器提供者实例
     * @param Properties $properties 应用属性配置实例
     * @param array $config 配置数组
     */
    public function __construct(DefinitionProvider $provider, ApplicationBase $app, Properties $properties)
    {
        $this->app = $app;
        $this->properties = $properties;
        $this->initSourceDefinitions($provider, $properties);
    }
    
    /**
     * 获取定义源
     *
     * @return array
     */
    protected function initSourceDefinitions(DefinitionProvider $provider, Properties $properties)
    {
        $provider->addDefinitionProivder($this);
        
        $config = (array)$properties['container'];
        
        // defintions
        $sourceDefinitions = [];
        $sourceDefinitions[] = ($this->app instanceof ConsoleApplication) ? ConsoleRequest::class : WebRequest::class;
        $sourceDefinitions[] = ($this->app instanceof ConsoleApplication) ? ConsoleResponse::class : WebResponse::class;
        
        // 别名
        $sourceDefinitions['alias'] = array_merge($this->alias, (array)$config['alias']);
        
        // 预定义
        foreach ((array)$config['definintions'] as $definition) {
            $sourceDefinitions[] = $definition;
        }
        
        $this->sourceDefinitions = array_merge($this->sourceDefinitions, $sourceDefinitions);
        $provider->addDefinitionFromArray($sourceDefinitions);
        
        // files
        $providerPath = $config['provider_path'];
        if (!$providerPath) {
            return;
        }
        
        $this->sourceFiles = [];
        if ($this->app->has('app.application.cache')) {
            $cacheInstance = $this->app->get('app.application.cache');
            $files = $cacheInstance->get('application.containers.files');
            
            
            // load files
            if (!is_array($files)) {
                $files = $this->getDefinitionFiles($providerPath);
                $cacheInstance->set('application.containers.files', $files);
            }
            $this->sourceFiles = $files;
        }
        $provider->addDefinitionFromFile($this->sourceFiles);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinition()
     */
    public function getDefinition(string $name)
    {
        switch ($name) {
            case __CLASS__:
                return new InstanceDefinition(__CLASS__, $this);
            case Request::class:
                return $this->getRequestDefinition();
            case Response::class:
                return $this->getResponseDefinition();
            case Router::class:
                return $this->getRouterDefinition();
            case Dispatcher::class:
                return $this->getDispatcherDefinition();
            case Cache::class:
                return $this->getCacheDefinition();
            case Configuration::class:
                return $this->getConfigDefinition();
            case Data::class:
                return $this->getDataDefinition();
            case Filter::class:
                return $this->getFilterDefinition();
            case View::class:
                return $this->getViewDefinition();
            case Lang::class:
                return $this->getLangDefinition();
            case Logger::class:
                return $this->getLoggerDefinition();
            case View::class:
                return $this->getViewDefinition();
            case ApplicationCache::class:
                return $this->getApplicationCacheDefinition();
            case HttpSession::class:
                return $this->getSessionDefinition();
            case HttpCookie::class:
                return $this->getCookieDefinition();
        }
        
        // 匹配请求
        if ($definition = $this->getRequestParamDefinition($name)) {
            return $definition;
        }
        
        // 匹配缓存
        if ($definition = $this->getCacheStoragerDefinition($name)) {
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
    }
    
    /**
     * 获取当前应用缓存实例
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getApplicationCacheDefinition()
    {
        return new CallableDefinition(ApplicationCache::class, function (ContainerInterface $container, Cache $cacheInstance) {
            if ($container->has(Cache::class)) {
                $cacheInstance = $container->get(Cache::class);
                $storagerId = Cache::getStoragerId(SingleCache::class);
                $cacheInstance->addStorager('application.cache', $storagerId, [
                    'path' => '',
                    'key' => 'application.cache'
                ]);
                $cacheStorager = $cacheInstance['application.cache'];
            }
            return new ApplicationCache($cacheStorager);
        });
    }
    
    /**
     * 获取request定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getRequestDefinition()
    {
        return new CallableDefinition(Request::class, function (ApplicationBase $app, Properties $properties) {
            $requestClass = ($app instanceof ConsoleApplication) ? ConsoleRequest::class : WebRequest::class;
            $request = $app->get($requestClass);
            if ($app instanceof ConsoleApplication) {
                if ($request->param['debug'] && $properties['debug.enabled'] && $properties['debug.event_listener']) {
                    $app->isDebug = true;
                    $properties['event.listeners.debug'] = $properties['debug.event_listener'];
                }
            }
            // controller
            $request->setControllerName($properties['controller.default']);
            $request->setControllerParamName($properties['controller.param']);
            
            // action
            $request->setActionName($properties['controller.action_default']);
            $request->setActionParamName($properties['controller.action_param']);
            return $request;
        });
    }
    
    /**
     * 获取request内的数据
     *
     * @param string $name
     * @return \Tiny\DI\Definition\CallableDefinition
     * @formatter:off
     */
    protected function getRequestParamDefinition($name)
    {
        if (in_array($name, [Get::class, Post::class, Param::class])) {
            return new CallableDefinition($name, function (Request $request) use ($name) {
                switch ($name) {
                    case Get::class:
                        return $request->get;
                    case Post::class:
                        return $request->post;
                    case Param::class:
                        return $request->param;
                }
            });
        }
    }
    
    /**
     * 获取response定义
     * 
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getResponseDefinition()
    {
        return new CallableDefinition(Response::class, function (ApplicationBase $app, Properties $properties) {
            $responseClass = ($app instanceof ConsoleApplication) ? ConsoleResponse::class : WebResponse::class;
            $response = $app->get($responseClass);
            $response->setCharset($this->properties['charset']);
            return $response;
        });
    }
    
    /**
     * 获取路由的容器定义
     *
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getRouterDefinition()
    {
        if (!$this->properties['router.enabled']) {
            return;
        }
        
        return new CallableDefinition(Router::class, function (Request $request, array $config) {
            // router config
            $config['routes'] = (array)$config['routers'];
            $config['rules'] = (array)$config['rules'];
            
            $routerInstance = new Router($request);
            
            // 注册路由
            foreach ($config['routes'] as $routerName => $routerclass) {
                $routerInstance->addRoute($routerName, $routerclass);
            }
            
            // 注册路由规则
            foreach ($config['rules'] as $rule) {
                $rule = (array)$rule;
                $routerInstance->addRouteRule($rule);
            }
            return $routerInstance;
        }, ['config' => $this->properties['router']]);
    }
    
    /**
     * 获取派发器的容器定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getDispatcherDefinition()
    {
        $config = [
            'controllerNamespace' => (string)$this->properties['namespaces.controller'],
            'actionSuffix' => (string)$this->properties['action.suffix']
        ];
        return new ObjectDefinition(Dispatcher::class, Dispatcher::class, $config);
    }
   
    /**
     * 获取缓存的定义
     *
     * @return CallableDefinition
     */
    protected function getCacheDefinition()
    {
        
        if (!$this->properties['cache.enabled']) {
            return;
        }
        
        return new CallableDefinition(Cache::class, function (array $config) {
            
            // 存储器映射
            $storagers = (array)$config['storagers'];
            foreach ($storagers as $storagerId => $storagerClass) {
                Cache::regStorager($storagerId, $storagerClass);
            }
            
            $defaultId = (string)$config['default_id'];
            $ttl = (int)$config['ttl'];
            $path = (string)$config['dir'];
            
            $cacheInstance = new Cache();
            $cacheInstance->setDefaultPath($path);
            $cacheInstance->setDefaultId($defaultId);
            $cacheInstance->setDefaultTtl($ttl);
            $caches = (array)$config['sources'];
            $storagerId = Cache::getStoragerId(SingleCache::class);
            
            foreach ($caches as $cacheConfig) {
                $cacheInstance->addStorager($cacheConfig['id'], $cacheConfig['storager'], $cacheConfig['options']);
            }
            return $cacheInstance;
        }, ['config' => (array)$this->properties['cache']]);
    }
    
    /**
     * 获取cache存储器的定义
     *
     * @param string $name
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getCacheStoragerDefinition(string $name)
    {
        
        if (!$this->properties['cache.enabled']) {
            return;
        }
        if (!Cache::getStoragerId($name)) {
            return;
        }
        return new CallableDefinition($name, function (Cache $cache, string $name){
            return $cache->getStoragerByClass($name);
        }, ['name' => $name]);
    }
  
    /**
     * 获取配置的容器定义
     *
     * @throws ApplicationException
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getConfigDefinition()
    {
        $config = (array)$this->properties['config'];
        if (!$config['enabled']) {
            return;
        }
        return new CallableDefinition(Configuration::class, function (ContainerInterface $container, array $config) {
            if (!$config['path']) {
                throw new ApplicationException("properties.config.path is not allow null!");
            }
            
            $configInstance = new Configuration($config['path']);
            if (!$config['cache']['enabled']) {
                return $configInstance;
            }
            
            if ($container->has('app.application.cache')) {
                $cacheInstance = $container->get('app.application.cache');
                $configData = $cacheInstance->get('application.config');
                if ($configData) {
                    $configInstance->setData($configData);
                } else {
                    $configData = $configInstance->get();
                    $cacheInstance->set('application.config', $configData);
                }
            }
            return $configInstance;
        }, ['config' => $config]);
    }

    /**
     * 获取数据操作池的定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getDataDefinition()
    {
        $config = (array)$this->properties['data'];
        if (!$config['enabled']) {
            return;
        }
        return new CallableDefinition(Data::class, function (ApplicationBase $app, array $config) {
            
            // 驱动
            foreach ((array)$config['drvers'] as $id => $className) {
                Data::regDataSourceDriver($id, $className);
            }
            // 
            $dataInstance = new Data();
            
            // 编码
            $charset = $config['charset'] ?: 'utf8';
            
            // 添加数据源
            foreach ((array)$config['sources'] as $sourceConfig) {
                $sourceConfig['def_charset'] = $charset;
                $sourceConfig['is_record'] = (bool)$app->isDebug;
                $dataInstance->addDataSource($sourceConfig);
            }
            return $dataInstance;
        }, ['config' => $config]);
    }
    
    /**
     * 过滤器定义
     * @return CallableDefinition
     */
    protected function getFilterDefinition()
    {
        $config = (array)$this->properties['filter'];
        if (!$config['enabled']) {
            return false;
        }
        
        return new CallableDefinition(Filter::class, function (ApplicationBase $app, array $config) {
            $filterInstance = new Filter();    
            $filterClass = ($app instanceof WebApplication) ? $config['web'] : $config['console'];
            if ($filterClass && is_array($filterClass)) {
                foreach ($filterClass as $fclass) {
                    $filterInstance->addFilter($fclass);
                }
            } elseif ($filterClass && is_string($filterClass)) {
                $filterInstance->addFilter($filterClass);
            }
            
            foreach ((array)$config['filters'] as $fname) {
                $filterInstance->addFilter($fname);
            }
            
            return $filterInstance;
        }, ['config' => $config]);
    }
    
    /**
     * 获取语言包容器定义
     *
     * @return boolean|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getLangDefinition()
    {
        $config = (array)$this->properties['lang'];
        if (!$config['enabled']) {
            return false;
        }
        return new CallableDefinition(Lang::class, function (ContainerInterface $container, array $config) {   
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
            if ($container->has('app.application.cache')) {
                $cacheInstance = $container->get('app.application.cache');
                $cacheKey = (string)$config['cache']['key'] ?: 'application.cache.lang';
                $langData = $cacheInstance->get($cacheKey);
                if ($langData && is_array($langData)) {
                    $langInstance->setData($langData);
                } else {
                    $langData = $langInstance->getData();
                    $cacheInstance->set($cacheKey, $langData);
                }
            }
            return $langInstance;
        }, ['config' => $config]);
    }
 
    /**
     * 获取日志生成器的容器定义
     *
     * @return void|\Tiny\DI\Definition\CallableDefinition
     */
    protected function getLoggerDefinition()
    {
        $config = (array)$this->properties['log'];
        if (!$config['enabled']) {
            return;
        }
        
        return new CallableDefinition(Logger::class, function (array $config) {
            $logger = new Logger();
            foreach ((array)$config['drivers'] as $type => $className) {
                Logger::regLogWriter($type, $className);
            }
            
            $writerConfig = ('file' === $config['writer']) ? [
                'path' => $config['path'],
            ] : [];
            
            $logger->addLogWriter($config['writer'], $writerConfig);
            return $logger;
        }, ['config' => $config]);
    }
    
    /**
     * 获取控制器的模型定义
     *
     * @param string $name
     * @return \Tiny\DI\Definition\ObjectDefinition|boolean
     */
    protected function getControllerDefinition(string $name)
    {
        if (strpos($name, $this->properties['namespaces.controller']) === 0) {
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
        if (strpos($name, $this->properties['namespaces.model']) === 0) {
            return new ObjectDefinition($name, $name);
        }
        return false;
    }
    
    /**
     * 视图定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getViewDefinition()
    {
        return new CallableDefinition(View::class,
            function (ContainerInterface $container, DefinitionProvider $provider, ApplicationBase $app,Properties $properties) {
                $config = (array)$properties['view'];
                
                $viewInstance = new View($app);
                
                $provider->addDefinitionProivder($viewInstance);
               
                $helpers = (array)$config['helpers'];
                $engines = (array)$config['engines'];
                $assigns = (array)$config['assign'] ?: [];
                
                // 如果开启了配置，则注入到视图模板的环境变量
                if ($properties['config.enabled']) {
                    $assigns['config'] = $container->get(Configuration::class);
                }
                
                // 默认为框架文件下的resource/mvc/view
                $defaultTemplateDir = TINY_FRAMEWORK_RESOURCE_PATH . 'mvc/view/';
                
                // application目录下为 application/views/default
                $templateTheme = $config['theme'] ?: 'default';
                $templateThemeDir = $config['basedir'] . $templateTheme . DIRECTORY_SEPARATOR;
                if ($properties['lang.enabled']) {
                    $assigns['lang'] = $container->get(Lang::class);
                }
                
                $templateDirs = [
                    $defaultTemplateDir,
                    $templateThemeDir
                ];
                
                // static
                $staticConfig = $config['static'];
                $staticBasedir = $staticConfig['basedir'];
                $staticPublicPath = $staticConfig['public_path'];
                $staticMinsize = (int)$staticConfig['minsize'];
                $staticExts = $staticConfig['exts'];
                
                // staticfile
                $engines[] = [
                    'engine' => StaticFile::class,
                    'config' => ['basedir' => $staticBasedir, 'public_path' => $staticPublicPath, 'minsize' => $staticMinsize],
                    'ext' => $staticExts
                ];
                
                // composer require tinyphp-ui; @formatter:off
                $uiconfig = $config['ui'];
                if ($uiconfig['enabled']) {
                    
                    // UI 模板路径
                    if ($uiconfig['template_dirname']) {
                        echo $uiconfig['template_dirname'];
                        $templateDirs[] = (string)$uiconfig['template_dirname'];
                    }
                    
                    // UI 助手注册
                    $uiHelperName = (string)$uiconfig['helper'];
                    if ($uiHelperName) {
                        $helpers[] = [ 'helper' => $uiHelperName];
                    }
                    
                    // 注册template的插件
                    $templatePlugin = (string)$uiconfig['template_plugin'];
                    if ($templatePlugin) {
                        $uiPublicPath = rtrim($staticBasedir, DIRECTORY_SEPARATOR) . ltrim($uiconfig['public_path'], DIRECTORY_SEPARATOR);
                        $templatePluginConfig = [
                            'public_path' => $uiPublicPath,
                            'inject' => $uiconfig['inject'],
                            'dev_enabled' => $uiconfig['dev_enabled'],
                            'dev_public_path' => $uiconfig['dev_public_path']
                        ];
                        $engines[] = [
                            'engine' => \Tiny\MVC\View\Engine\Template::class,
                            'config' => [],
                            'plugins' => [[ 'plugin' => $templatePlugin, 'config' => $templatePluginConfig ]]
                        ];
                        
                    }
                }
                foreach ((array)$config['paths'] as $tid => $path) {
                    if (!is_string($tid)) {
                        $tid = count($templateDirs);
                    }
                    $templateDirs[$tid] = $path;
                }
                
                // @formatter:on
                // 设置模板搜索目录
                $templateDirs = array_reverse($templateDirs);
                $viewInstance->setTemplateDir($templateDirs);
                
                // engine初始化
                foreach ($engines as $econfig) {
                    $viewInstance->bindEngine($econfig);
                }
                
                // helper初始化
                foreach ($helpers as $econfig) {
                    $viewInstance->bindHelper($econfig);
                }
                
                $viewInstance->setCompileDir($config['compile']);
                $viewInstance->assign($assigns);
                return $viewInstance;
            });
    }
    
    /**
     * 获取HttpCookie的实例定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getCookieDefinition()
    {
        if (!$this->app instanceof WebApplication) {
            return;
        }
        return new CallableDefinition(HttpCookie::class, function (Properties $prop) {
            $config = (array)$prop['cookie'];
            $config['data'] = $_COOKIE;
            return new HttpCookie($config);
        });
    }
    
    /**
     * 获取HttpSession 的实例定义
     *
     * @return \Tiny\DI\Definition\CallableDefinition
     */
    protected function getSessionDefinition()
    {
        if (!$this->app instanceof WebApplication) {
            return;
        }
        return new CallableDefinition(HttpSession::class, function (Properties $prop) {
            
            return new HttpSession((array)$prop['session']);
        });
    }
    
    /**
     * 加载application的container配置文件路径
     *
     * @param string $path 加载路径
     */
    protected function getDefinitionFiles($path)
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->getDefinitionFiles($p);
            }
            return $this->sourceFiles;
        }
        
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $this->getDefinitionFiles(rtrim($path, DIRECTORY_SEPARATOR) . '/' . $file);
            }
            return $this->sourceFiles;
        }
        if ('php' === pathinfo($path, PATHINFO_EXTENSION) && !in_array($path, $this->sourceFiles)) {
            $this->sourceFiles[] = $path;
        }
        return $this->sourceFiles;
    }
}
?>