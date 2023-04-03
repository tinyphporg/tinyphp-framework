<?php

/**
 *
 * @copyright (C), 2013-, King.
 * @name Runtime.php
 * @author King
 * @version Beta 1.0
 * @Date: 2019年11月12日上午10:07:58
 * @Description 运行时库
 * @Class List
 *        1.RuntimeException Runtime异常类
 *        2.Runtime 运行时类
 *        3.Autoloader 自动加载类
 *        4.ICacheHandler runtime缓存接口
 *
 * @Function List 1.
 * @History King 2019年11月12日上午10:07:58 第一次建立该文件
 *          King 2020年02月19日下午15:44:00 stable 1.0 审定稳定版本
 *
 */
namespace Tiny\Runtime;

use Tiny\MVC\Application\ApplicationBase;
use Tiny\DI\Container;
use Tiny\DI\ContainerInterface;
use Tiny\Event\EventManager;
use Tiny\MVC\Application\ConsoleApplication;
use Tiny\MVC\Application\WebApplication;
use Tiny\DI\Definition\Provider\DefinitionProvider;

// 定义框架所在路径
define('TINY_FRAMEWORK_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// 引入ENV和自动加载类
require_once __DIR__ . '/Environment.php';
require_once __DIR__ . '/RuntimeCache.php';
require_once __DIR__ . '/Autoloader.php';

/**
 * 运行时类
 *
 * @package Runtime
 * @since 2019年11月12日上午10:11:41
 * @final 2019年11月12日上午10:11:41
 */
class Runtime
{   
    /**
     * WEB运行时
     * 
     * @var integer
     */
    const RUNTIME_WEB = 0;
    
    /**
     * 命令行运行时
     * 
     * @var integer
     */
    const RUNTIME_CONSOLE = 1;
    
    /**
     * RPC运行时
     * 
     * @var integer
     */
    const RUNTIME_RPC = 2;
    
    /**
     * 环境参数实例
     *
     * @var Environment
     */
    public $env;
    
    /**
     * 运行缓存
     * 
     * @var RuntimeCache
     */
    public $runtimecache;
    
    /**
     * Runtime创建的自动加载对象实例
     *
     * @var Autoloader
     */
    public $autoloader;
    
    /**
     * Runtime创建的异常处理实例
     *
     * @var ExceptionHandler
     */
    public $exceptionHandler;
    
    /**
     * Runtime创建的容器实例
     *
     * @var ContainerInterface
     */
    public $container;
    
    /**
     * application与runtime mode的映射表
     *
     * @var array 不同运行时模式对应的application类
     *      0 WEB模式
     *      1 CONSOLE模式
     *      2 RPC模式
     */
    protected static $applicationClassMap = [
        self::RUNTIME_WEB => WebApplication::class,
        self::RUNTIME_CONSOLE => ConsoleApplication::class
    ];
    
    /**
     *  当前runtime的唯一实例
     * @var Runtime
     */
    protected static $runtime;
    
    /**
     * 开始时间戳
     *
     * @var float
     */
    protected $starttime;
    
    /**
     * Runtime创建的应用程序实例 必须集成ApplicationBase
     *
     * @var ApplicationBase
     */
    protected $application;
    
    /**
     * 获取Runtime的实例
     * 
     * @return \Tiny\Runtime\Runtime
     */
    public static function getInstance()
    {
        if(!self::$runtime) {
            self::$runtime = new Runtime();
        }
        return self::$runtime;
    }
        
    /**
     * 注册或者替换已有的Application class与runtime mode 的映射
     *
     * @param int $mode 运行模式 web|console|rpc
     * @param string $applicationClass 继承了ApplicationBase的application类名
     * @return bool
     */
    public static function registerApplicationClass($runtimeMode, $applicationClass): bool
    {
        if (!key_exists($runtimeMode, self::$applicationMap)) {
            return false;
        }
        self::$applicationClassMap[$runtimeMode] = $applicationClass;
        return true;
    }
    
    /**
     * 获取当前运行时的应用实例
     *
     * @return ApplicationBase
     */
    public function getApplication()
    {
        return $this->container->get(ApplicationBase::class);
    }
    
    /**
     * 设置application实例
     *
     * @param ApplicationBase $app
     */
    public function setApplication(ApplicationBase $application)
    {
        $this->container->set(ApplicationBase::class, $application);
        return $this->container->set(get_class($application), $application);
    }
    
    /**
     * 创建application实例
     *
     * @param string $applicationPath 当前应用实例路径
     * @param string|array $profile 配置文件路径
     * @return \Tiny\MVC\Application\ApplicationBase
     */
    public function createApplication($applicationPath, $profile = null)
    {
        $runtimeMode = $this->env['RUNTIME_MODE'];
        $applicationClass = self::$applicationClassMap[$runtimeMode];
        $application = new $applicationClass($this->container, $applicationPath, $profile);
        if (!$application instanceof ApplicationBase) {
            throw new RuntimeException("Failed to create app instance, class %s must inherit %s", $applicationClass, ApplicationBase::class);
        }
        return $application;
    }
    
    /**
     * 导入自动加载的类命名空间与路径映射
     *
     * @param string $namespace 命名空间
     * @param string|array $path 类库加载绝对路径 array为多个加载路径
     * @return bool
     */
    public function addToNamespacePathMap(string $namespace, $path)
    {
        return $this->autoloader->addToNamespacePathMap($namespace, $path);
    }
    
    /**
     * 导入自定加载的类与路径映射
     *
     * @param string $className 类名
     * @param string $path
     */
    public function addToClassPathMap($className, $path)
    {
        return $this->autoloader->addToClassPathMap($className, $path);
    }
    
    /**
     * 获取所加载的库映射路径集合
     *
     * @return array
     */
    public function getNamespacePathMap()
    {
        return $this->autoloader->getNamepsacePathMap();
    }
    
    /**
     * 获取运行时间计数
     *
     * @return number
     */
    public function getLifetime()
    {
        return microtime(true) - $this->starttime;
    }
    
    /**
     * 构建基本运行环境所需的各种实例
     */
    protected function __construct()
    {
        $this->starttime =  microtime(true);
        
        // default
        $env = new Environment();
        $this->env = $env;
        
        //  运行缓存
        $this->runtimecache = new RuntimeCache($env['TINY_CACHE_PATH'], $env['APP_ENV']);
        
        
        // autoloader
        $loadedClasses = $this->runtimecache->get($env['RUNTIME_CACHE_AUTOLOADER_ID']);
        if (!is_array($loadedClasses)) {
            $loadedClasses = [];
        }
        $this->autoloader = new Autoloader($loadedClasses);
        $this->autoloader->addToNamespacePathMap('Tiny', TINY_FRAMEWORK_PATH);
        
        // autoloader
        
        
        // build container
        $proivder = new DefinitionProvider([]);
        $this->container = new Container($proivder);
        $this->container->set(RuntimeCache::class, $this->runtimecache);
        // eventmanager
        $eventManager = new EventManager($this->container);
        $this->container->set(EventManager::class, $eventManager);
        
        // exception handler
        $this->exceptionHandler = new ExceptionHandler($eventManager);
        
        // init
        $this->container->set(self::class, $this);
        $this->container->set(Environment::class, $this->env);
        $this->container->set(Autoloader::class, $this->autoloader);
        $this->container->set(ExceptionHandler::class, $this->exceptionHandler);
        $this->container->set(DefinitionProvider::class, $proivder);
    }
    
    /**
     * 
     */
    public function __destruct()
    {
        if ($this->autoloader->getLoadedClassPathMap()) {
            $this->runtimecache->set($this->env['RUNTIME_CACHE_AUTOLOADER_ID'], $this->autoloader->getClassPathMap());
        }
        // $this->runtimecache->save();
    }
}
?>