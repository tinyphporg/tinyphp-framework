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

// 定义框架资源路径
define('TINY_FRAMEWORK_RESOURCE_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR);

// WEB模式
define('TINY_RUNTIME_MODE_WEB', 0);

// 命令行模式
define('TINY_RUNTIME_MODE_CONSOLE', 1);

// 远程服务模式
define('TINY_RUNTIME_MODE_RPC', 2);

/**
 * 运行时主体类
 *
 * @package Runtime
 * @since 2019年11月12日上午10:11:41
 * @final 2019年11月12日上午10:11:41
 */
class Runtime
{
    
    /**
     * 框架名名称
     *
     * @var string
     */
    const FRAMEWORK_NAME = 'Tiny Framework For PHP';
    
    /**
     * 框架版本号
     *
     * @var string
     */
    const FRAMEWORK_VERSION = '2.0.0';
    
    /**
     * 框架所在目录
     *
     * @var string
     */
    const FRAMEWORK_PATH = TINY_FRAMEWORK_PATH;
    
    /**
     * 环境参数实例
     *
     * @var Environment
     */
    public $env;
    
    /**
     * application与runtime mode的映射表
     *
     * @var array 不同运行时模式对应的application类
     *      WEB模式
     *      CONSOLE模式
     *      RPC模式
     */
    protected static $applicationMap = [
        TINY_RUNTIME_MODE_CONSOLE => ConsoleApplication::class,
        TINY_RUNTIME_MODE_WEB => WebApplication::class,
    ];
    
    /**
     * Runtime创建的应用程序实例 必须集成ApplicationBase
     *
     * @var ApplicationBase
     */
    protected $application;
    
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
     * 开始时间戳
     *
     * @var float
     */
    protected $startTimeline;
    
    /**
     * 构建基本运行环境所需的各种实例
     */
    public function __construct()
    {
        $this->startTimeline =  microtime(true);
        
        // default
        $this->env = new Environment();
        
        // autoloader
        $this->autoloader = new Autoloader();
        $this->autoloader->addToNamespacePathMap('Tiny', self::FRAMEWORK_PATH);
        
        // build container
        $proivder = new DefinitionProvider([]);
        $this->container = new Container($proivder);
        
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
     * 注册或者替换已有的Application类与runtime mode 的映射
     *
     * @param int $mode 运行模式 web|console|rpc
     * @param string $applicationClass 继承了ApplicationBase的application类名
     * @return bool
     */
    public static function registerApplication($runtimeMode, $applicationClass): bool
    {
        if (!key_exists($runtimeMode, self::$applicationMap)) {
            return false;
        }
        self::$applicationMap[$runtimeMode] = $applicationClass;
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
        $applicationClass = self::$applicationMap[$runtimeMode];
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
    public function getRuntimeTotal()
    {
        return microtime(true) - $this->startTimeline;
    }
}
?>