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
use Tiny\Runtime\Environment;
use Tiny\Runtime\RuntimeCache;

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
    protected const NAMESPACE_MODEL = 'Model';
    
    /**
     * 当前应用实例
     * 
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * @var Environment
     */
    protected $env;
    
    /**
     * 运行时内存
     * 
     * @var RuntimeCache
     */
    protected $runtimeCache;
    
    
    /**
     * 是否加载了application的定义源
     *
     * @var boolean|array
     */
    protected $applicationDefinitionFiles = false;
    
    protected $spaths = [];
    
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
    public function __construct(ApplicationBase $app, $profile = null)
    {
        $this->app = $app;
        $this->env = $app->get(Environment::class);
        $this->runtimeCache = $app->get(RuntimeCache::class);
        
        // 读取配置并初始化
        $profiles = $this->initProfiles($profile);
        parent::__construct($profiles);
        $this->init();
    }
    
    /**
     * 
     * @param mixed $profile
     * @return array
     */
    protected function initProfiles($profile)
    {
        $profiles = [];
        $appPath = $this->app->path;
        $this->spaths['app'] = $appPath;
        $profiles[] = __DIR__ . '/Properties/profile.php';
        
        // env prod|dev|test ...
        $envName = $this->env['APP_ENV'];
        if ($envName) {
            $profiles[] = $appPath . sprintf('/properties/profile.%s.php', $envName);
        }
        
        // 自定义配置文件
        if (is_string($profile)) {
            $profiles[] = $profile;
        } 
        elseif (is_array($profile)) {
            foreach($profile as $pfile) {
                if (is_string($pfile)) {
                    $profiles[] = $pfile;
                }
            }
        }
        
        $this->profiles = $profiles;
        return $profiles;
    }
    
    /**
     * 初始化
     */
    protected function init()
    {

        $this->initData();        
        $this->initDebug();
        $this->initNamespace();
        $this->initPath();
        $this->initAutoloader();
        
        // 执行命令行应用的一些初始化配置
        $this->initInConsoleApplication();
        $this->initModule();
    }
    
    /**
     * 初始化配置数据
     */
    protected function initData()
    {
        $data = $this->runtimeCache->get('application.properties');
        if (is_array($data) && $data) {
            $this->setData($data);
        }
        else {
            $data = $this->get();
            if (!$data || !is_array($data)) {
                throw new ApplicationException('Properties Data parse error: must be an array!');
            }
            $this->runtimeCache->set('application.properties', $data);
        }
        $this->parseEnvs($this->data);
        $this->parseSpath($this->data['spath']);
        print_r($this->spaths);
        
        $this->parseSpath($this->data);
        
        print_r($this->data);;
    }
    
    /**
     * 解析环境参数
     * 
     * @param mixed $data
     */
    protected function parseEnvs(& $data) 
    {
        
        if(is_string($data)) {
            $env = $this->env;
            $data = preg_replace_callback('/\{env.([^\{\}]+)\}/', function($matches)use($env){
                $nodeName = $matches[1];
                return isset($env[$nodeName]) ? $env[$nodeName] : $matches[0];
            }, $data);
        } 
        elseif(is_array($data)) {
            foreach ($data as & $d) {
                $this->parseEnvs($d);
            }
        }
    }
    
    protected function parseSpath(&$data, $name = null, $isPath  = false)
    {
        if (is_string($data)) {
            $spaths = $this->spaths;
            $matches = [];
            if (!preg_match('/\{path.([^\{\}]+)\}/', $data, $matches)) {
                if ($isPath) {
                    $this->spaths[$name] = $data;
                }
                return;
            }
            $nodeName = $matches[1];
            if (!isset($spaths[$nodeName])) {
                return;
            }
            $nodeData = $spaths[$nodeName];
            $this->spaths[$name]  = $nodeData;
            $data = preg_replace('/\{path.([^\{\}]+)\}/', $nodeData, $data);
        } elseif(is_array($data)) {
            foreach ($data as $n => &$d) {
                
                if ($name == null && $n == 'spath'){
                    $nodeName = null;
                    $isPath = true;
                } else{
                    $isPath = false;
                   $nodeName = ($name == null ? $n : $name . '.' . $n);
                }
                $this->parseSpath($d, $nodeName, $isPath);
            }
        }
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
        
        if (!($rpath['0'] === '/' || preg_match('/^[C-Z]:/', $rpath))) {
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

        // app
        $isRealpath = (bool)$this['autoloader.is_realpath'];
        
        // namespaces
        $namespaces = (array)$this['autoloader.namespaces'];
        foreach ($namespaces as $ns => $p) {
            $path = $isRealpath ? $p : $this[$p];
            $this['autoloader.namespaces.' . $ns] = $path;
        }
       
        // classes
        $classes = (array)$this['autoloader.classes'];
        foreach ($classes as $class => $p) {
            $path = $isRealpath ? $p : $this[$p];  
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
        // 初始化打包器配置
        $this->initBuilder();
        
        // 初始化守护进程配置
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
     * 是否开启命令行下的打包机制
     */
    protected function initBuilder()
    {
        $config = $this['builder'];
        
        // 配置是否开启打包器
        if (!$config || !$config['enabled'] || !$config['event_listener']) {
            return;
        }
        
        // 注册事件监听器
        $this['event.listeners.builder'] = $config['event_listener'];
    }
    
    /**
     * 是否开启服务守护进程
     */
    protected function initDaemon()
    {
        $config = $this['daemon'];
        
        // 配置是否开启守护进程
        if (!$config || !$config['enabled'] || !$config['event_listener']) {
            return;
        }
        
        // 注册守护进程的事件监听器
        $this['event.listeners.daemon'] = $config['event_listener'];
    }
}
?>