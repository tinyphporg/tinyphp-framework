<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ModuleEventListener.php
 * @author King
 * @version stable 2.0
 * @Date 2022年3月28日下午1:45:39
 * @Class List class
 * @Function List function_container
 * @History King 2022年3月28日下午1:45:39 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Module;

use Tiny\MVC\Event\MvcEvent;
use Tiny\Cache\Storager\PHP;
use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\Application\Properties;
use Tiny\Config\Configuration;
use Tiny\MVC\Event\BootstrapEventListenerInterface;
use Tiny\Runtime\Runtime;
use Tiny\MVC\Event\RequestEventListenerInterface;
use Tiny\MVC\Application\WebApplication;
use Tiny\DI\Definition\Provider\DefinitionProviderInterface;
use Tiny\DI\Definition\Provider\DefinitionProvider;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\Cache\CacheInterface;
use Tiny\UI\UIException;

/**
 * 模块事件监听类
 *
 * @package Tiny.MVC.Module
 * @since 2022年3月28日下午1:46:43
 * @final 2022年3月28日下午1:46:43
 */
class ModuleManager implements \ArrayAccess,\Iterator,\Countable, RequestEventListenerInterface, BootstrapEventListenerInterface, DefinitionProviderInterface
{
    
    /**
     * 默认配置
     *
     * @var array
     */
    const DEFAULT_SETTINGS = [
        'NAMESPACE_CONTROLLER' => '\\Controller',
        'NAMESPACE_CONTROLLER_CONSOLE' => '\\Controller\\Console',
        'NAMESPACE_MODEL' => '\\Model',
        'NAMESPACE_EVENT' => '\\Event',
        'ACTION_SUFFIX' => 'Action',
        'CONFIG_PATH' => 'config/',
        'LANG_PATH' => 'lang/',
        'CONTROLLER_PATH' => 'controllers/web/',
        'CONTROLLER_CONSOLE_PATH' => 'controllers/console/',
        'MODEL_PATH' => 'models/',
        'EVENT_PATH' => 'events/',
        'LIBRARY_PATH' => 'librarys/',
        'LIBRARY_GLOBAL_PATH' => '/librarys/global',
        'VIEW_PATH' => 'views/',
    ];
    
    /**
     * 当前运行时实例
     *
     * @var Runtime
     */
    protected $runtime;
    
    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * app配置实例
     *
     * @var Properties
     */
    protected $properties;
    
    /**
     * 缓存实例
     * 
     * @var CacheInterface
     */
    protected $cacheInstance;
    
    /**
     * 缓存KEY
     *
     * @var string
     */
    protected $cacheKey = 'application.module';
    
    /**
     * 是否需要更新缓存
     * @var boolean
     */
    protected $isUpdateCache = false;
    
    /**
     * module 模块数组
     *
     * @var array
     */
    protected $modules = [];
    
    /**
     * 已启用的模块
     *
     * @var array
     */
    protected $activatedModules = [];
    
    /**
     * 禁止的模块列表
     * 
     * @var array
     */
    protected $disabledModules = [];
    
    /**
     * 启用模块的所有命名空间
     * 
     * @var array
     */
    protected $activateNamespaces = [];
    
    /**
     * 解析后的命名空间
     * 
     * @var array
     */
    protected $parsedNamespaces = [];
    /**
     * module 实例数组
     *
     * @var array
     */
    protected $moduleInstances = [];
    
    /**
     * 各模块的视图文件夹映射表
     *
     * @var array
     */
    protected $viewPaths = [];
    

    
    /**
     * 初始化
     *
     * @param PHP $cache
     */
    public function __construct(DefinitionProvider $provider, ApplicationBase $app, Runtime $runtime)
    {
        $this->app = $app;
        $this->properties = $app->properties;
        $provider->addDefinitionProivder($this);
        $this->runtime = $runtime;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinition()
     */
    public function getDefinition($name)
    {
        if (!$this->activateNamespaces) {
            return;
        }
        foreach($this->activateNamespaces as $namespace => $moduleName) {
            if (strpos($name, $namespace) !== 0) {
                continue;
            }
            $moduleConfig = $this->modules[$moduleName];
            if (in_array($name, $moduleConfig['ignores'])) {
                continue;    
            }
            return new ObjectDefinition($name, $name, [
                self::class => $this,
                Module::class => $this->getModule($moduleName),
                'moduleName' => $moduleName
            ]);
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinitions()
     */
    public function getDefinitions(): array
    {
        return [];
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\RequestEventListenerInterface::onBeginRequest()
     */
    public function onBeginRequest(MvcEvent $event, array $params)
    {
        $this->disabledModules = (array)$this->properties['module.disabled_modules'];
        
        // 非调试模式下读取缓存
        $this->modules = (array)$this->getCacheInstance()->get($this->cacheKey);
        
        if (!$this->modules) {
            $modulePath = $this->properties['module.path'];
            if (!$modulePath) {
                return;
            }
            $this->scanModulePath($modulePath);
            if (!$this->modules) {
                return;
            }
            $this->isUpdateCache = true;
        }
        // 预加载启用的模块配置
        foreach ($this->modules as $moduleName => $moduleConfig) {
            if ($moduleConfig['init']) {
                $this->activatedModules[$moduleName] = &$this->modules[$moduleName];
            }
        }
        // init modules
        foreach ($this->modules as $moduleName => &$moduleConfig) {
            
            if (!$moduleConfig['init']) {
                continue;
            }
            
            $ret = $this->isRequired($moduleConfig);
            if ($ret !== true) {
                $moduleConfig['disbaled'] = true;
                $moduleConfig['errormsg'] = $ret;
                unset($this->activatedModules[$moduleName]);
                continue;
            }
            $this->initModule($moduleName);
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\BootstrapEventListenerInterface::onBootstrap()
     */
    public function onBootstrap(MvcEvent $event, array $params)
    {
        $activateModules = (array)$this->properties['module.activated_modules'];
        foreach ($activateModules as $aid => $moduleName) {
            if (!key_exists($moduleName, $this->modules)) {
                unset($activateModules[$aid]);
                continue;
            }
            if ($this->modules[$moduleName]['init']) {
                unset($activateModules[$aid]);
                continue;
            }
            $this->activatedModules[$moduleName] = &$this->modules[$moduleName];
        }
        
        foreach ($activateModules as $moduleName) {
            $moduleConfig = &$this->modules[$moduleName];
            $ret = $this->isRequired($moduleConfig);
            if ($ret !== true) {
                $moduleConfig['disbaled'] = true;
                $moduleConfig['errormsg'] = $ret;
                unset($this->activatedModules[$moduleName]);
                continue;
            }
            $this->initModule($moduleName);
        }
        $this->properties['view.assign.modules'] = $this;
        
        // 非调试模式下读取缓存
        if ($this->isUpdateCache) {
            $this->getCacheInstance()->set($this->cacheKey, $this->modules);
        }
        
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\RequestEventListenerInterface::onEndRequest()
     */
    public function onEndRequest(MvcEvent $event, array $params)
    {
    }
    
    /**
     *
     * @param string $name
     * @return boolean
     */
    public function has(string $name)
    {
        if ($this->modules && key_exists($name, $this->modules)) {
            
            $moduleConfig = $this->modules[$name];
            if ($moduleConfig['disabled']) {
                return false;
            }
            if (!$moduleConfig['activated']) {
                return false;
            }
            return true;
        }
    }
    
    /**
     *
     * @param string $name
     * @return
     */
    public function getControllerNamespace(string $name)
    {
        if (!key_exists($name, $this->modules)) {
            return null;
        }
        return $this->modules[$name]['controllerNamespace'];
    }
    
    /**
     * 根据模块名创建模块实例
     *
     * @param string $name
     */
    public function getModule($name)
    {
        if (!$this->moduleInstances[$name]) {
            
            if (!key_exists($name, $this->modules)) {
                throw new ModuleException(sprintf('Module %s is not exists!', $name));
            }
            $moduleConfig = $this->modules[$name];
            if ($moduleConfig['disabled']) {
                throw new ModuleException(sprintf('Module %s is disabled!', $name));
            }
            if (!$moduleConfig['activated']) {
                throw new ModuleException(sprintf('Module %s is not activated!', $name));
            }
            $this->moduleInstances[$name] = new Module($this, $this->modules[$name]);
        }
        return $this->moduleInstances[$name];
    }
    
    /**
     *
     * @param array $moduleConfig
     * @return boolean|string
     */
    protected function isRequired($moduleConfig)
    {
        if (!$moduleConfig['requires']) {
            return true;
        }
        
        foreach ($moduleConfig['requires'] as $mname => $require) {
            
            if (!key_exists($mname, $this->activatedModules)) {
                return sprintf('Activated Module %s require %s is not exists', $moduleConfig['name'], $mname);
            }
            
            $version = trim($this->modules[$mname]['version']);
            $rop = $require['operator'];
            $rversion = $require['version'];
            $compare = version_compare($version, $rversion);
            
            // @formatter:off
            if ($compare == 0 && !in_array($rop, ['>=', '<=', '=', ''])) {
                return sprintf('Module %s Require %s version %s require %s %s', $moduleConfig['name'], $mname, $rversion, $rop, $version);
            }
            if ($compare == 1 && !in_array($rop, ['>', '>='])) {
                return sprintf('Module %s Require %s version %s require %s %s', $moduleConfig['name'], $mname, $rversion, $rop, $version);
            }
            if ($compare == -1 && !in_array($rop, ['<', '<='])) {
                return sprintf('Module %s Require %s version %s require %s %s', $moduleConfig['name'], $mname, $rversion, $rop, $version);
            }
            // @formatter:on
        }
        return true;
    }
    
    /**
     * 模块是否存在
     *
     * @param string $moduleName
     * @return boolean
     */
    public function offsetExists($moduleName)
    {
        return $this->has($moduleName);
    }
    
    /**
     * 获取模块
     *
     * @param string $moduleName
     * @return Module
     */
    public function offsetGet($moduleName)
    {
        return $this->getModule($moduleName);
    }
    
    /**
     * 不允许设置模块
     *
     * @param string $moduleName
     * @param Module $value
     */
    public function offsetSet($moduleName, $value)
    {
    }
    
    /**
     * 不允许删除模块
     *
     * @param string $moduleName
     */
    public function offsetUnset($moduleName)
    {
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Countable::count()
     */
    public function count()
    {
        return count($this->activatedModules);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Iterator::current()
     */
    public function current()
    {
        return current($this->moduleInstances);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Iterator::next()
     */
    public function next()
    {
        return next($this->moduleInstances);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Iterator::key()
     */
    public function key()
    {
        return key($this->moduleInstances);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Iterator::rewind()
     */
    public function rewind()
    {
        return reset($this->moduleInstances);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Iterator::valid()
     */
    public function valid()
    {
        return $this->key() !== null;
    }
    
    /**
     * 返回当前缓存操作实例
     * 
     * @return \Tiny\Cache\CacheInterface
     */
    protected function getCacheInstance()
    {
        if (!$this->cacheInstance) {
            $this->cacheInstance = $this->app->get('app.application.cache');
        }
        return $this->cacheInstance;
    }
    
    /**
     *
     * @param string $moduleName
     * @param string $defaultModule
     */
    protected function initModule($moduleName)
    {
       
        if (!key_exists($moduleName, $this->modules)) {
            return;
        }
        $moduleConfig = & $this->modules[$moduleName];
        if (!$moduleConfig) {
            return;
        }
        $this->modules[$moduleName]['activated'] = true;
        
        // autoloader
        foreach ($moduleConfig['namespaces'] as $namespace => $path) {
            $this->runtime->addToNamespacePathMap($namespace, $path);
            if ($namespace == '*') {
                continue;
            }
            $this->activateNamespaces[$namespace] = $moduleName;
        }
        
        // event listener
        $eventListener = $moduleConfig['eventlistener'];
        if (is_array($eventListener)) {
            foreach ($eventListener as $el) {
                if (is_array($el) && key_exists('class', $el)) {
                    $priority = key_exists('priority', $el) ? (int)$el['priority'] :0;
                    $this->app->addEventListener($el['class'], $priority);
                }
            }     
        } elseif (is_string($eventListener)) {
            $this->app->addEventListener($eventListener);
        }
        
        // route
        $routes = (array)$moduleConfig['routes'];
        $routeRules = (array)$this->properties['router.rules'];
        foreach ($routes as $route) {
            $routeRules[] = $route;
        }
        $this->properties['router.rules'] = $routeRules;
        
        // view
        if ($moduleConfig['path']['view']) {
            $viewNode = sprintf('view.paths.%s', $moduleName);
            $this->properties[$viewNode] = $moduleConfig['path']['view'];
        }
     
        //static
        $sconfig = $moduleConfig['static'];
        if ($this->initStatic($sconfig)) {
            $moduleConfig['static']['completed'] = true;
            $this->isUpdateCache = true;
        }
        
        //
       return  $this->getModule($moduleName);
    }
    
    /**
     * 初始化静态
     * 
     * @param array $sconfig
     */
    protected function initStatic($sconfig)
    {
        if (!$sconfig || !$sconfig['enabled'] || $sconfig['completed']) {
            return;
        }
        if (!$sconfig['web'] && $this->app instanceof  WebApplication) {
            return;
        }
        foreach((array)$sconfig['paths'] as $path) {
            $this->copytoStaticDir($path['from'], $path['to'], $path['exclude']);
        }
        return true;
    }
    
    /**
     * 复制文件夹去安装路径
     *
     * @param string $sourcePath 源文件路径
     * @param string $installPath 安装路径
     * @throws UIException
     * @return void|boolean
     */
    protected function copytoStaticDir($sourcePath, $toPath, $exclude = false)
    {
        if (!is_dir($sourcePath)) {
            return false;
        }
        if (preg_match("/^(|\*|\/|\/(usr|home|root|lib|lib64|etc|var)\/?|)$/i", $toPath)) {
            return;
        }
                
        if (file_exists($toPath) && !is_dir($toPath)) {
                throw new UIException(sprintf('%s is a file!', $toPath));
        }
        if (!file_exists($toPath)) {
            mkdir($toPath, 0777, TRUE);
        }
        
        $files = scandir($sourcePath);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $filename = $sourcePath . '/' . $file;
            $tofilename = $toPath . '/' . $file;
            
            if (is_dir($filename)) {
                $this->copytoStaticDir($filename, $tofilename, $exclude);
                continue;
            }
            
            // 更新最新文件
            if (is_file($tofilename) && filemtime($tofilename) >= filemtime($filename)) {
                return;
            }
            if ($exclude && preg_match($exclude, $filename)) {
                return;
            }
            
            $ret = copy($filename, $tofilename);
            if (!$ret) {
                throw new UIException(sprintf('copy failed: %s to %s', $filename, $tofilename));
            }
        }
    }
    
    /**
     * 扫描并解析模块配置
     *
     * @param string $path
     * @param boolean $isChildNode 是否为模块下子目录 不予扫描
     */
    protected function scanModulePath($path, $isChildNode = true)
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->scanModulePath($p);
            }
            return;
        }
        
        // 目录扫描
        if (is_dir($path)) {
            $configPath = $path . '/module.json';
            if (is_file($configPath)) {
                return $this->parseModuleProfile($configPath);
            }
            
            if (!$isChildNode) {
                return;
            }
            
            $paths = scandir($path);
            foreach ($paths as $p) {
                if ('.' == $p || '..' == $p) {
                    continue;
                }
                
                $childPath = rtrim($path, '/') . '/' . $p;
                if (is_file($childPath)) {
                    continue;
                }
                
                if (is_dir($childPath)) {
                    $this->scanModulePath($childPath, false);
                }
            }
        }
        
        // 配置文件，即判断文件名是否为module.json
        if (is_file($path) && basename($path) == 'module.json') {
            $this->parseModuleProfile($path);
        }
    }
    
    /**
     * 解析模块的配置属性文件
     *
     * @param string $path 属性配置文件
     * @return null|array
     */
    protected function parseModuleProfile($path)
    {
        $moduleConfig = [];
        $profile = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }
        
        
        // module name
        $name = (string)$profile['name'];
        if (!preg_match('/^[a-z][a-z0-9\-_]*$/', $name)) {
            return;
        }
        
        $moduleConfig['profile'] = $profile;
        $moduleConfig['desc'] = (string)$profile['desc'];
        $moduleConfig['disabled'] = (bool)$profile['disabled'];
        if (in_array($name, $this->disabledModules)) {
            $moduleConfig['disabled'] = true;
        }
        if ($moduleConfig['disabled']) {
            return;
        }

        
        // module namespace;
        $namespace = (string)$profile['namespace'];
        if (!preg_match('/[A-Z][a-z]+/', $namespace)) {
            return;
        }

        // profile setting
        $mconfig = (array)$this->properties['module'];
        if (key_exists($name, $mconfig)) {
            $mconfig = (array)$mconfig[$name];
            if ($mconfig) {
                $moduleConfig['profile']['setting'] = array_replace_recursive((array)$profile['setting'], $mconfig);
            }
        }
        
        // module version
        $moduleConfig['name'] = $name;
        $moduleConfig['basedir'] = dirname($path) . DIRECTORY_SEPARATOR;
        $moduleConfig['index'] = (string)$profile['index'];
        $moduleConfig['namespace'] = $namespace;
        $moduleConfig['eventlistener'] = $profile['eventlistener'];
        $moduleConfig['version'] = (string)$profile['version'];
        $moduleConfig['init'] = (bool)$profile['init'];
        $moduleConfig['ignores'] = (array)$profile['autoloader']['ignores'];
        $moduleConfig['errormsg'] = '';
        $moduleConfig['lang'] = [];
        
        // requires
        $this->parseRequires($moduleConfig);
        
        // router
        $this->parseRoutes($moduleConfig);
        
        // module path
        $this->parsePaths($moduleConfig);
        
        // config
        $this->parseConfig($moduleConfig);
        
        // 解析语言包
        $this->parseLang($moduleConfig);

        // namespaces
        $this->parseNamespaces($moduleConfig);
        
        // static
        $this->parseStatic($moduleConfig);
        
        // 命名空间唯一性
        $namespace = $moduleConfig['namespace'];
        if (in_array($namespace, $this->parsedNamespaces)) {
            return;
        }
        $this->parsedNamespaces[] = $namespace;

        $this->modules[$name] = $moduleConfig;
        return $moduleConfig;
    }
    
    /**
     * 解析模块路径
     * 
     * @param array $moduleConfig
     * @param string $basedir
     */
    protected function parsePaths(array &$moduleConfig)
    {
        $name = $moduleConfig['name'];
        $basedir = $moduleConfig['basedir'];
        $index = $moduleConfig['index'];
        $indexdir = $index ? $this->properties->path($index, [], $basedir) : $basedir;
        $paths = [
            'config' => self::DEFAULT_SETTINGS['CONFIG_PATH'],
            'lang' => self::DEFAULT_SETTINGS['LANG_PATH'],
            'controller' => self::DEFAULT_SETTINGS['CONTROLLER_PATH'],
            'controller_console' => self::DEFAULT_SETTINGS['CONTROLLER_CONSOLE_PATH'],
            'model' => self::DEFAULT_SETTINGS['MODEL_PATH'],
            'event' => self::DEFAULT_SETTINGS['EVENT_PATH'],
            'view' => self::DEFAULT_SETTINGS['VIEW_PATH'],
            'library' => self::DEFAULT_SETTINGS['LIBRARY_PATH'],
            'global' => self::DEFAULT_SETTINGS['LIBRARY_GLOBAL_PATH']
        ];
        
        $parsedPaths = [];
        foreach ($paths as $key => &$value) {
            $value = $indexdir. $value;
            $parsedPaths['module.' . $name . '.' . $key] = $value;
        }
        $paths['profile'] = $basedir;
        $paths['basedir'] = $basedir;
        $paths['indexdir'] = $indexdir;
        
        // view
        if (!is_dir($paths['view'])) {
            $paths['view'] = null;
        }
        
        $moduleConfig['parsedPaths'] = $parsedPaths;
        $moduleConfig['path'] = $paths;
    }
    
    /**
     * 解析路由
     * 
     * @param array $moduleConfig
     */
    protected function parseRoutes(& $moduleConfig)
    {
        $routes = (array)$moduleConfig['profile']['routes'];
        foreach ($routes as & $route) {
            $route['rule'] = (array)$route['rule'];
            if (!key_exists('module', $route['rule'])) {
                $route['rule']['module'] = $moduleConfig['name'];
            }
        }
        $moduleConfig['routes'] = $routes;
    }
    
    /**
     * 解析模块配置
     * 
     * @param array $moduleConfig
     */
    protected function parseConfig(array &$moduleConfig)
    {
        $profile = $moduleConfig['profile'];
        $paths = $moduleConfig['path'];
        $moduleConfig['config'] = [];
        if ($profile['config'] && is_dir($paths['config'])) {
            $configData = is_array($profile['config']) ? $profile['config'] : [];
            $configInstance = new Configuration($paths['config']);
            $configData = array_merge($configData, $configInstance->get());
            $moduleConfig['config'] = $configData;
        }
    }
    
    /**
     * 解析语言包
     * 
     * @param array $moduleConfig
     */
    protected function parseLang(array &$moduleConfig)
    {
        $profile = $moduleConfig['profile'];
        $paths = $moduleConfig['path'];
        if ($profile['lang'] && is_dir($paths['lang'])) {
            $configData = is_array($profile['lang']) ? $profile['lang'] : [];
            $configInstance = new Configuration($paths['lang']);
            $configData = array_merge($configData, (array)$configInstance->get());
            $moduleConfig['lang'] = $configData;
        } else {
            $moduleConfig['lang'] = [];
        }
    }
    
    /**
     * 解析requires
     * 
     * @param array $moduleConfig
     * @param array $requires
     */
    protected function parseRequires(& $moduleConfig)
    {
        $requires = (array)$moduleConfig['profile']['require'];
        foreach ($requires as $mname => & $req) {
            if (preg_match("/^\s*(>=|>|<|<=|=|)\s*([a-z0-9][a-z0-9\.]*)\s*$/i", $req, $out)) {
                $req = [
                    'operator' => $out[1],
                    'version' => $out[2]
                ];
            } else {
                unset($requires[$mname]);
            }
        }
        $moduleConfig['requires'] = $requires;
    }
    
    /**
     * 解析命名空间
     * 
     * @param array $moduleConfig
     * @param string $name
     * @param string $profile
     * @param array $paths
     * @param array $parsedPaths
     * @param string $basedir
     */
    protected function parseNamespaces(&$moduleConfig)
    {
        $name = $moduleConfig['name'];
        $profile = $moduleConfig['profile'];
        $basedir = $moduleConfig['basedir'];
        
        $paths = $moduleConfig['path'];
        $indexdir = $paths['indexdir'];
        $parsedPaths = $moduleConfig['parsedPaths'];
        
        // root namespace
        $namespace = (string)$profile['namespace'] ?: ucfirst($name);
        $namespace = rtrim($namespace, '\\');
        
        // 命名空间
        $controllerNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_CONTROLLER'];
        $consoleControllerNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_CONTROLLER_CONSOLE'];
        $modelNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_MODEL'];
        $eventNamespace = $namespace . self::DEFAULT_SETTINGS['NAMESPACE_EVENT'];
        
        $defaultNamespaces = [
            $namespace => $paths['library'],
            $controllerNamespace => $paths['controller'],
            $consoleControllerNamespace => $paths['controller_console'],
            $modelNamespace => $paths['model'],
            $eventNamespace => $paths['event'],
        ];

        // 格式化命名空间
        $moduleConfig['controllerNamespace'] = $this->app instanceof WebApplication ? $controllerNamespace : $consoleControllerNamespace;
        $namespaces = array_merge($defaultNamespaces, (array)$profile['autoloader']['namespaces']);
        $moduleConfig['namespaces'] = $this->formatNamespaces($namespace, $namespaces);
        foreach ($moduleConfig['namespaces'] as &$npath) {        
            $npath = $this->properties->path($npath, $parsedPaths, $indexdir);
        }
        
        //global
        $globalPath = (string)$profile['autoloader']['global'];
        $globalPath = $this->properties->path($globalPath ?: $paths['global'], $parsedPaths, $indexdir);
        if ($globalPath) {
            $moduleConfig['namespaces']['*'] = $globalPath;
        }
    }
    
    /**
     * 解析配置的静态文件复制信息
     * 
     * @param array $moduleConfig
     */
    protected function parseStatic(&$moduleConfig)
    {
        $name = $moduleConfig['name'];
        $profile = $moduleConfig['profile'];
        $basedir = $moduleConfig['basedir'];
        $paths = $moduleConfig['path'];
        $parsedPaths = $moduleConfig['parsedPaths'];
        
        // 静态资源存放目录
        $toStaticDir = $this->properties['view.static.basedir'] . $name . DIRECTORY_SEPARATOR;
        $toPublicPath = $this->properties['view.static.public_path'] . $name . DIRECTORY_SEPARATOR;
        // 格式化静态资源配置
        $staticConfig = (array)$profile['autoloader']['static'];
        $static = ['enabled' => true, 'completed' => false, 'web' => true];
        if (array_key_exists('web', $staticConfig) && !$staticConfig['web']) {
            $static['web'] = false;
        }
        
        $staticPaths = [];
        $paths = (array)$staticConfig['paths'];
        foreach ($paths as $path) {
            if (is_string($path)) {
                $from = $this->properties->path($path, $parsedPaths, $basedir);
                $to = $toStaticDir;
                $staticPaths[] = ['from' => $from, 'to' => $to, 'exclude' => false];
                continue;
            }
            if (is_array($path)) {
                if (!key_exists('from', $path)) {
                    continue;
                }
                $from = $this->properties->path($path['from'], $parsedPaths, $basedir);
                $to = key_exists('to', $path) ? $this->properties->path($path['to'], [], $toStaticDir) : $toStaticDir;
                $exclude = (string)$path['exclude'] ?? false;
                $staticPaths[] = ['from' => $from, 'to' => $to, 'exclude' => $exclude];
            }
        }
        
        $static['paths'] = $staticPaths;
        if (!$staticPaths) {
            $static['enabled'] = false;
            $static['completed'] = true;
        }
        
        //update moduleconfig
        $moduleConfig['static'] = $static;
        $moduleConfig['profile']['setting']['public_path'] = $toPublicPath;
        
    }
    
    /**
     * 格式化命名空间
     * @param string $namespace
     * @param array $namespaces
     * @return []
     */
    protected function formatNamespaces($namespace, $namespaces) 
    {
        $rnamespaces = [];
        foreach ($namespaces as $childNamespace => $path) {
            $childNamespace = rtrim($childNamespace, '\\');
            if ($childNamespace!== $namespace && strpos($childNamespace, $namespace . '\\') !== 0) {
                continue;
            }
            $rnamespaces[$childNamespace] = $path;
        }
        return $rnamespaces;
    }
}
?>