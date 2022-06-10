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
use Tiny\Runtime\Runtime;

/**
 * application属性
 *
 * @package Tiny.MVC.Application
 * @since 2021年11月27日 下午1:01:32
 * @final 2021年11月27日下午1:01:32
 */
class Properties extends Configuration
{
    /**
     * application的命名空间
     *
     * @var String
     */
    protected const  NAMESPACE_APPLICATION = 'App';
    
    /**
     * 默认的控制器命名空间
     * 
     * @var string
     */
    protected const NAMESPACE_CONTROLLER = 'Controller';
    
    /**
     * 默认的命令行控制器命名空间
     * 
     * @var string
     */
    protected const NAMESPACE_CONTROLLER_CONSOLE = 'Controller\Console';
    
    /**
     * 模型的默认命名空间
     * @var string
     */
    protected const NEMSPACE_MODEL = 'Model';
    
    /**
     * 当前应用实例
     * 
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * 是否加载了application的定义源
     *
     * @var boolean|array
     */
    protected $applicationDefinitionFiles = false;
    
    /**
     * 已经解析的路径信息
     *
     * @var array
     */
    protected $parsedPaths = [];
    
    /**
     * 构造函数
     *
     * @param ApplicationBase $app
     * @param string|array $cpath 配置文件路径
     */
    public function __construct(ApplicationBase $app, $profile)
    {
        parent::__construct($profile);
        $this->app = $app;
        $this->initDebug();
        $this->initNamespace();
        $this->initPath();
        $this->initAutoloader();
        $this->initInConsoleApplication();
        $this->initModule();
        $this->initUI();
    }
    
    /**
     * 解析包含替换字符串的绝对路径
     * {app}
     * {src.vendor}
     *
     * @param string $path
     */
    public function path($path, array $parsedPaths = [], string $defpath = '')
    {
        $parsedPaths = array_merge($parsedPaths, $this->parsedPaths);
        $rpath = preg_replace_callback("/{([a-z0-9_]+(\.[a-z0-9_]+)*)}/is", function ($matchs) use ($parsedPaths) {
            $pathName = $matchs[1];
            if (key_exists($pathName, $parsedPaths)) {
                return $parsedPaths[$pathName];
            }
            if (!strpos($pathName, '.') && key_exists('src.' . $pathName, $parsedPaths)) {
                return $parsedPaths['src.' . $pathName];
            }
        }, $path);
        
        if ($rpath['0'] !== '/') {
            $rpath = ($defpath ? $defpath : $this->app->path) . $rpath;
        }
        return $this->getAbsolutePath($rpath);
    }
    
    /**
     * 初始化debug
     */
    protected function initDebug()
    {
        $config = (array)$this['debug'];
        if (!$config['enabled'] || !$config['event_listener']) {
            return;
        }
        $this->app->isDebug = true;
        $this['event.listeners.debug'] = $config['event_listener'];
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
        $appNamespace = (string)$this['namespace'] ?: self::NAMESPACE_APPLICATION;
        
        // controller namespace
        $namespaces = (array)$this['controller.namespace'];
        if ($this->app instanceof ConsoleApplication) {
            $controllerNamespace = (string)$namespaces['console'] ?: self::NAMESPACE_CONTROLLER_CONSOLE;
        } else {
            $controllerNamespace = (string)$namespaces['default'] ?: self::NAMESPACE_CONTROLLER;   
        }
        $controllerNamespace = $appNamespace . '\\' . $controllerNamespace;
        
        // model namespace;
        $modelNamespace = (string)$this['model.namespace'] ?: self::NAMESPACE_MODEL;
        $modelNamespace = $appNamespace . '\\' . $modelNamespace;
        $this['namespaces']  = ['app' => $appNamespace, 'controller' => $controllerNamespace, 'model' => $modelNamespace];
        $this->app->namespace = $appNamespace;
    }
    
    /**
     * 初始化配置路径
     */
    protected function initPath()
    {
        $this->parsedPaths['app'] = $this->app->path;
        $paths = $this['path'];
        foreach ($paths as $p) {
            $path = $this[$p];
            if (!$path) {
                continue;
            }
            if (is_array($path)) {
                $parsedPaths = [];
                foreach ($path as $k => $ipath) {
                    $parsedPaths[$k] = $this->path($ipath);
                    $this->parsedPaths[$p . '.' . $k] = $this->path($ipath);
                }
                $this->set($p, $parsedPaths);
                continue;
            }
            $parsedPath = $this->path($path);
            $this->parsedPaths[$p] = $parsedPath;
            $this->set($p, $parsedPath);
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
        
        $path = str_replace([
            '/',
            '\\'
        ], DIRECTORY_SEPARATOR, $path);
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
     * 应用于命令行时的初始化
     */
    protected function initInConsoleApplication()
    {
        if (!$this->app instanceof ConsoleApplication) {
            return;
        }
        $this->initBuilder();
        $this->initDaemon();
    }
    
    /**
     * 初始化模块化管理
     */
    protected function initModule()
    {
        if ($this['module.enabled'] && $this['module.event_listener']) {
            $this['event.listeners.module'] = $this['module.event_listener'];
        }
    }
    
    /**
     * 初始化UI库的配置
     */
    protected function initUI()
    {
        // 视图调试配置
        if ($this['view.ui.enabled'] && $this['view.ui.dev_enabled'] && $this['view.ui.dev_event_listener']) {
            $this['event.listeners.ui_dev'] = $this['view.ui.dev_event_listener'];
        }
        if ($this->app instanceof ConsoleApplication) {
            $this->initUIInstaller();
        }
    }
    
    /**
     * 初始化命令行下的打包机制
     */
    protected function initBuilder()
    {
        $config = $this['builder'];
        if (!$config || !$config['enabled'] || !$config['event_listener']) {
            return;
        }
        $this['event.listeners.builder'] = $config['event_listener'];
    }
    
    /**
     * 初始化服务守护进程
     */
    protected function initDaemon()
    {
        $config = $this['daemon'];
        if (!$config || !$config['enabled'] || !$config['event_listener']) {
            return;
        }
        $this['event.listeners.daemon'] = $config['event_listener'];
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
        if (!$installConfig || !$installConfig['event_listener']) {
            return;
        }
        $installDir = $installConfig['install_dir'] ?: 'tinyphp-ui/';
        $installConfig['install_dir'] = rtrim($this['view.static.basedir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($installDir, DIRECTORY_SEPARATOR);
        $this['event.listeners.uiinstaller'] = (string)$installConfig['event_listener'];
    }
}
?>