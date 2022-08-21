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
use Tiny\MVC\Event\BootstrapEventListenerInterface;
use Tiny\Runtime\Runtime;
use Tiny\MVC\Event\RequestEventListenerInterface;
use Tiny\MVC\Application\WebApplication;
use Tiny\DI\ContainerInterface;
use Tiny\MVC\Module\Source\ModuleSource;
use Tiny\MVC\Module\Util\StaticCopyer;
use Tiny\MVC\Module\Parser\ModuleParser;
use Tiny\Event\EventManager;

/**
 * 模块事件监听类
 *
 * @package Tiny.MVC.Module
 * @since 2022年3月28日下午1:46:43
 * @final 2022年3月28日下午1:46:43
 */
class ModuleManager implements \ArrayAccess, \Iterator, \Countable, RequestEventListenerInterface, BootstrapEventListenerInterface
{
    /**
     * 默认配置
     * 
     * @var array
     */
    const DEFALT_CONFIG = [
        'path' => null,
        'cache' => true,
        'disabled_modules' => [],
        'activate_modules' => [],
    ];
    
    /**
     * app配置实例
     *
     * @var Properties
     */
    protected $properties;
    
    /**
     * 容器实例
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 模块容器定义源实例
     *
     * @var ModuleProvider
     */
    protected $moduleProvider;
    
    /**
     * 配置
     *
     * @var array
     */
    protected $config;
    
    /**
     * 所有的模块配置数组
     *
     * @var array
     */
    public $modules = [];
    
    /**
     * 是否需要更新缓存
     *
     * @var boolean
     */
    protected $isUpdated = false;
    
    /**
     * 待启用的模块名称数组
     *
     * @var array
     */
    protected $activateModuleNames = [];
    
    /**
     * 已启用的模块
     *
     * @var array
     */
    protected $activatedModuleNames = [];
    
    /**
     * 已启用的模块命名空间
     *
     * @var array
     */
    protected $activatedNamespace = [];
    
    /**
     * beginrequest即初始化的模块数组
     *
     * @var array
     */
    protected $initedModuleNames = [];
    
    /**
     * 正在解决依赖的模块数组
     *
     * @var array
     */
    protected $requiringModules = [];
    
    /**
     * 初始化
     *
     * @param PHP $cache
     */
    public function __construct(ContainerInterface $container, Properties $properties)
    {
        $this->properties = $properties;
        $this->container = $container;
        $this->config = array_merge(self::DEFALT_CONFIG, (array)$properties['module']);
        $this->moduleProvider = new ModuleProvider($this, $container);
    }
    
    /**
     * 根据模块名获取模块配置数组
     *
     * @param string $moduleName
     * @return array|NULL
     */
    public function getModuleConfig($moduleName)
    {
        return key_exists($moduleName, $this->modules) ? $this->modules[$moduleName] : null;
    }
    
    /**
     * 通过名称获取模块实例
     * 
     * @param string $moduleName 模块名称
     * @return Module
     */
    public function get($moduleName) {
        return $this->container->get(ModuleProvider::MODULE_CONTAINER_PREFIX . $moduleName);
    }
    
    /**
     * 是否存在指定名称的模块实例
     * 
     * @param string $moduleName 模块名
     * @return boolean
     */
    public function has($moduleName) {
        return $this->container->has(ModuleProvider::MODULE_CONTAINER_PREFIX . $moduleName);
    }
    
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\RequestEventListenerInterface::onBeginRequest()
     */
    public function onBeginRequest(MvcEvent $event, array $params)
    {        
        // search profiles
        $moduleSource = $this->container->get(ModuleSource::class);      
        $modules = $moduleSource->readFrom($this->config['path'], (bool)$this->config['cache'], (array)$this->config['disabled_modules']);
        if (!$modules) {
            return;
        }        
        // modules 标记了init的模块一定会加载
        $this->modules = $modules;
        
        // 添加入视图预设变量
        $this->properties['view.assign.modules'] = $this;    
        $this->initInitedModuleNames();
    }
    
    /**
     * 启用或者弃用模块
     *
     * 必须在onbootstrap前设置才可生效
     * 不存在该模块的配置，或者在beginRequest阶段已经启用过则不执行。
     *
     * @param string $moduleName 模块名
     * @param boolean $isActivated 启用或弃用
     */
    public function activateModule($moduleName, bool $isActivated = true)
    {
        if (!key_exists($moduleName, $this->modules) || key_exists($moduleName, $this->activatedModuleNames)) {
            return;
        }
        $this->activateModuleNames[$moduleName] = $isActivated;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\BootstrapEventListenerInterface::onBootstrap()
     */
    public function onBootstrap(MvcEvent $event, array $params)
    {
        // 获取自定义profile.php配置的待启用模块
        $activateModuleNames = (array)$this->config['activated_modules'];
        foreach ($activateModuleNames as $amame) {
            if (!key_exists($amame, $this->activateModuleNames)) {
                $this->activatedModuleNames[$amame] = true;
            }
        }
        
        // 启用
        foreach ($this->activateModuleNames as $moduleName => $isActivate) {
            if (!$isActivate) {
                $this->modules[$moduleName]['activated'] = $isActivate;
            }
            $this->initmodule($moduleName);
        }
        
        // 缓存
        $this->container->get(ModuleSource::class)->saveToCache($this->modules, $this->isUpdated);
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
        return $this->get($moduleName);
    }
    
    /**
     * 不允许设置模块
     *
     * @param string $moduleName
     * @param Module $value
     */
    public function offsetSet($moduleName, $value)
    {
        throw new ModuleException('Module manager is read-only and cannot be deleted or reset!');
    }
    
    /**
     * 不允许删除模块
     *
     * @param string $moduleName
     */
    public function offsetUnset($moduleName)
    {
        throw new ModuleException('Module manager is read-only and cannot be deleted or reset!');
    }
    
    /**
     *
     * @return number
     */
    public function count()
    {
        return count($this->modules);
    }
    
    /**
     * 
     * @return boolean
     */
    public function rewind()
    {
        return reset($this->modules);
    }
    
    /**
     * 
     * @return mixed
     */
    public function next()
    {
        return next($this->modules);
    }
    
    /**
     * 
     * @return mixed
     */
    public function current()
    {
        $module = current($this->modules);
        return $this->get($module['name']);
    }
    
    /**
     * 
     * @return mixed
     */
    public function key()
    {
        return key($this->modules);
    }
    
    /**
     * 
     * @return boolean
     */
    public function valid()
    {
        return key($this->modules) !== null;
    }
    
    /**
     * 获取初始化的模块数组
     *
     * @return array[]
     */
    protected function initInitedModuleNames()
    {
        // 检索所有在init阶段需要加载的模块
        $initedModuleNames = [];
        foreach ($this->modules as $moduleName => $module) {
            if (!$module['inited']) {
                continue;
            }
            $initedModuleNames[] = $moduleName;
            $this->activateModuleNames[$moduleName] = true;
        }
        
        // init
        foreach ($initedModuleNames as $moduleName) {
            $this->initModule($moduleName, true);
        }
    }
    
    /**
     *
     * @param array $moduleConfig
     * @return boolean|string
     */
    public function isRequired($moduleName, $requires)
    {
        if (!$requires) {
            return true;
        }
        if (in_array($moduleName, array_column($requires, 'module'))) {
            return;
        }
        
        array_multisort(array_column($requires, 'status'), $requires, SORT_ASC);
        foreach ($requires as $require) {
            if ($require['status'] === 0) {
                return;
            }
            $rmname = $require['module'];
            if (!key_exists($rmname, $this->modules)) {
                return;
            }
            $rmodule = $this->modules[$rmname];
            $version = trim($rmodule['version']);
            $rop = $require['operator'];
            $rversion = $require['version'];
            $compare = version_compare($version, $rversion);
            
            // @formatter:off
            if ($compare == 0 && !in_array($rop, ['>=', '<=', '=', ''])) {
                return;
            }
            if ($compare == 1 && !in_array($rop, ['>', '>='])) {
                return;
            }
            if ($compare == -1 && !in_array($rop, ['<', '<='])) {
                return;
            }
            // @formatter:on
        }
        return true;
    }
    
    /**
     *
     * @param string $moduleName
     * @param string $defaultModule
     */
    protected function initModule(string $moduleName, bool $isInited = false)
    {
        if (in_array($moduleName, $this->activatedModuleNames)) {
            return;
        }
        // 是否有配置
        if (!key_exists($moduleName, $this->modules)) {
            return;
        }
        
        // 验证待启用模块命名空间 不能重复
        $module = &$this->modules[$moduleName];
        
        if (!$module['activated']) {
            $module['status']['errno'] = 1000;
            $module['status']['errmsg'] = 'Module initialization failed, modules %s activating is not allowed';
            return;
        }
        
        if ($module['disbaled']) {
            $module['status']['errno'] = 1001;
            $module['status']['errmsg'] = 'Module initialization failed, modules %s is  disbaled';
            return;
        }
        
        if (in_array($module['namespace'], $this->activatedNamespace)) {
            $module['disabled'] = true;
            $module['status']['errno'] = 1002;
            $module['status']['errmsg'] = sprintf('Module initialization failed, namespace %s is exists', $module['namespace']);
            return;
        }
        
        // 验证requires关系
        if (!$this->isRequired($moduleName, $module['requires'])) {
            $module['disabled'] = true;
            $module['status']['errno'] = 1003;
            $module['status']['errmsg'] = 'Module initialization failed, dependency tree error!';
            return;
        }
        
        // init namespaces
        $this->initModuleNamespaces($moduleName, (array)$module['namespace']['namespaces']);
        
        // event listener
        $this->initModuleEventListeners($moduleName, $module['eventlistener']);
        
        // route
        $this->initModuleRoutes($moduleName, (array)$module['routes']);
        
        // view
        $this->initModuleViewPaths($moduleName, $module['path']['view']);
        
        // static
        $this->initModuleStaticFiles($moduleName, $module['static']);
        
        // 更新状态
        if ($isInited) {
            $module['status']['inited'] = true;
        }
        $module['status']['activated'] = true;
        $this->activatedModuleNames[$moduleName] = $moduleName;
    }
    
    /**
     * 初始化模块的命名空间
     *
     * @param array $namespaces 命名空间数组
     * @param array $moduleName 模块名
     */
    protected function initModuleNamespaces(string $moduleName, array $namespaces)
    {
        $runtime = $this->container->get(Runtime::class);
        foreach ($namespaces as $namespace => $path) {
            $runtime->addToNamespacePathMap($namespace, $path);
            
            // 模块名不允许自动注入设置的全局命名空间
            if ($namespace == '*') {
                continue;
            }
            $this->moduleProvider->setActivateModuleName($namespace, $moduleName);
        }
    }
    
    /**
     * 初始化模块的事件监听器
     *
     * @param string $moduleName
     * @param string|array $eventlistener
     */
    protected function initModuleEventListeners(string $moduleName, $eventListener)
    {
        if (!$eventListener) {
            return;
        }
        $eventManager = $this->container->get(EventManager::class);
        if (is_string($eventListener)) {
            return $eventManager->addEventListener($eventListener);
        }
        if (!is_array($eventListener)) {
            return;
        }
        foreach ($eventListener as $listener) {
            if (is_array($listener) && key_exists('class', $listener)) {
                $priority = key_exists('priority', $listener) ? (int)$listener['priority'] : 0;
                $eventManager->addEventListener($listener['class'], $priority);
            }
        }
    }
    
    /**
     * 出货模块的路由规则
     *
     * @param string $moduleName 模块名
     * @param array $routes 路由规则
     */
    protected function initModuleRoutes(string $moduleName, array $routes)
    {
        $routeRules = (array)$this->properties['router.rules'];
        foreach ($routes as $route) {
            $routeRules[] = $route;
        }
        $this->properties['router.rules'] = $routeRules;
    }
    
    /**
     * 初始化模块的视图路径
     *
     * @param string $moduleName
     * @param string $viewPath
     */
    protected function initModuleViewPaths(string $moduleName, $viewPath)
    {
        if (!$viewPath) {
            return;
        }
        $viewNode = sprintf('view.paths.%s', $moduleName);
        $this->properties[$viewNode] = $viewPath;
    }
    
    /**
     * 初始化模块的静态文件
     *
     * @param array $staticConfig
     */
    protected function initModuleStaticFiles($moduleName, $sconfig)
    {
        if (!$sconfig || !$sconfig['enabled'] || $sconfig['completed']) {
            return;
        }
        $app = $this->container->get(ApplicationBase::class);
        if (!$sconfig['web'] && $app instanceof WebApplication) {
            return;
        }
        // 静态copy
        $staticCopyer = $this->container->get(StaticCopyer::class);
        foreach ((array)$sconfig['paths'] as $path) {
            $staticCopyer->copyto($path['from'], $path['to'], $path['exclude'], $path['replace']);
        }
        
        $this->isUpdated = true;
        $this->modules[$moduleName]['static']['completed'] = true;
        return true;
    }
}
?>