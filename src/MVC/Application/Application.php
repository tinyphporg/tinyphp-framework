<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Application.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月26日下午6:38:24 0 第一次建立该文件
 *          King 2021年11月26日下午6:38:24 1 修改
 *          King 2021年11月26日下午6:38:24 stable 1.0.01 审定
 */
namespace Tiny\MVC\Application;

use Tiny\Config\Configuration;
use Tiny\MVC\ApplicationBase;
use Tiny\Cache\Cache;
use Tiny\DI\ContainerInterface;
use Tiny\Data\Data;
use Tiny\Runtime\RuntimeCacheItem;
use Tiny\MVC\ApplicationException;
use Tiny\Lang\Lang;
use Tiny\MVC\View\View;
use Tiny\DI\Container;
use Tiny\Tiny;
use const Tiny\MVC\TINY_MVC_RESOURCES;
use Tiny\MVC\ConsoleApplication;
use Tiny\DI\Definition\DefinitionInterface;
use Tiny\DI\Definition\SelfResolvingDefinition;
use Tiny\DI\Definition\DefinitionProviderInterface;
use Tiny\Runtime\Runtime;
use Tiny\MVC\Router\Router;
use Tiny\DI\Definition\DefinitionProivder;
use Tiny\MVC\Bootstrap\Bootstrap;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\Definition\CallableDefinition;
use Tiny\Runtime\Environment;
use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Request\ConsoleRequest;
use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;
use Tiny\MVC\Response\ConsoleResponse;
use Tiny\MVC\Response\WebResponse;



/**
 * application属性
 *
 * @package Tiny.MVC.Application
 * @since 2021年11月27日 下午1:01:32
 * @final 2021年11月27日下午1:01:32
 */
class Properties extends Configuration implements DefinitionProviderInterface
{

    /**
     * @var ApplicationBase
     */
    protected ApplicationBase $app;
    
    /**
     * 源定义
     * 
     * @var array
     */
    protected $sourceDefinitions = [
      // Router::class,
    ];
    
    protected $classAlias = [];
    
    public function __construct($cpath, ApplicationBase $app)
    {
        $this->app = $app;
        parent::__construct($cpath);
        $this->initPath();
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\DefinitionProviderInterface::getDefinition()
     */
    public function getDefinition(string $name)
    {
        
        switch ($name) {
            case Request::class:
                return $this->getDefinitionFromClassAlias($name);
            case Response::class:
                return $this->getDefinitionFromClassAlias($name);
            case Router::class:
                echo "aaa";
                return $this->getRouterDefinition();
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\DefinitionProviderInterface::getDefinitions()
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }
    
    /**
     * 注册日志句柄
     * 
     * @autowired
     * @param Runtime $runtime
     */
    protected function autowireExceptionHandler(Runtime $runtime, ApplicationBase $app)
    {
        if (!(bool)$this['exception.enabled'])
        {
            return;
        }
        return $runtime->regExceptionHandler($app);
    }
    
    
    /**
     * 
     * @autowired
     * @param DefinitionProivder $proivder
     * @param ContainerInterface $container
     */
    protected function initDefinitions(DefinitionProivder $proivder, ContainerInterface $container, Environment $env)
    {
        $proivder = $container->get(DefinitionProivder::class);
        
        
        $sourceDefinitions = $this->sourceDefinitions;
        
        // Request
        $requestClassName = $env->isConsole() ?  ConsoleRequest::class : WebRequest::class;
        $sourceDefinitions[] = $requestClassName;
        $this->classAlias[Request::class] = $requestClassName;
        
        // Response
        $responseClassName = $env->isConsole() ? ConsoleResponse::class : WebResponse::class;
        $sourceDefinitions[] = $responseClassName;
        $this->classAlias[Response::class] = $responseClassName;
        
        // bootstrap
        if ($this['bootstrap.enabled'] && $this['bootstrap.class'])
        {
            $bootstrapClassName = $this['bootstrap.class'];
            $sourceDefinitions[] = $bootstrapClassName;
            $sourceDefinitions[Bootstrap::class] = function(ContainerInterface $container) use($bootstrapClassName) {
                return $container->get($bootstrapClassName);
            }; 
        }
        
        // router
        
        //
        
        $proivder->addDefinitionFromArray($sourceDefinitions);
        $proivder->addDefinitionFromPath($this->properties['container.config_path']); 
        $proivder->addDefinitionProivder($this);
    }
    
    /**
     * @autowired
     */
    protected function init(ApplicationBase $app)
    {
        // timezone 
        $timezone = $this['timezone'] ?: 'PRC';
        if ($timezone !== date_default_timezone_get())
        {
            date_default_timezone_set($timezone);
        }
        
        //charset 
        if (!$this['charset'])
        {
            $this['charset'] = 'zh_cn';
        }
       
        // app namespace
        $this->namespace = (string)$this->properties['app.namespace'] ?: 'App';
        
        // controller namespace
        $cnamespace = $this->properties['controller.namespace'];
        $controllerNamespace =  ($app instanceof ConsoleApplication) ? $cnamespace['console'] : $cnamespace['default'];  
        $controllerNamespace = ((string)$controllerNamespace ?: $this->controllerNamespace);
        $this->controllerNamespace = '\\' . $this->namespace . '\\' . ((string)$controllerNamespace ?: $this->controllerNamespace);
        
        // model namespace
        $modelNameSpace = (string)$this->properties['model.namespace'] ?: $this->modelNameSpace;
        $this->modelNameSpace = '\\' . $this->namespace . '\\' . $modelNameSpace;  
    }
    
    
    /**
     */
    protected function initPath()
    {
        $appPath = $this->app->path;
        $paths = $this->get('path');
        $runtimePath = $this->get('app.runtime');

        if (! $runtimePath)
        {
            $runtimePath = $appPath . 'runtime/';
        }
        if ($runtimePath && 0 === strpos($runtimePath, 'runtime'))
        {
            $runtimePath = $appPath . $runtimePath;
        }
        foreach ($paths as $p)
        {
            $path = $this->get($p);
            if (! $path)
            {
                continue;
            }
            if (0 === strpos($path, 'runtime'))
            {
                $rpath = preg_replace("/\/+/", "/", $runtimePath . substr($path, 7));
                if (! file_exists($rpath))
                {
                    mkdir($rpath, 0777, TRUE);
                }
                
                $this->set($p, $rpath);
                continue;
            }
            
            $rpath = $this->getAbsolutePath($appPath . $path);
            $this->set($p, $rpath);
        }
    }
    
    protected function getAbsolutePath($path) 
    {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return (($path[0] == DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '') . implode(DIRECTORY_SEPARATOR, $absolutes) . DIRECTORY_SEPARATOR;
    }
    
    /**
     * @autowired
     * 初始化加载类库
     *
     * @return void
     */
    protected function initImport(ApplicationBase $app, Runtime $runtime)
    {
        $namespace = (string)$this['app.namespace'] ?: 'App';        
        $runtime->import($app->path, $namespace);
        
        $prop = $this['autoloader'];
        $libs = (array)$this['autoloader.librarys'];
        $isNoRealpath = (bool)$this['autoloader.no_realpath'];
        if (!$libs)
        {
            return;
        }
        foreach ($prop['librarys'] as $ns => $p)
        {
            $path = $isNoRealpath ? $p : $this[$p];
            $runtime->import($path, $ns);
        }
    }
    
    
    protected function initDebug()
    {
        $debugConfig = $this->get('debug');
        if ($debugConfig['enabled'] && $debugConfig['plugin'])
        {
            $this->set('plugins.debug', $debugConfig['plugin']);
        }
    }
    
    protected function getDefinitionFromClassAlias($name)
    {
        if (!key_exists($name, $this->classAlias))
        {
            return false;
        }
        
        $className = $this->classAlias[$name];
        return new CallableDefinition($name, function(ContainerInterface $container) use($name, $className){
            $classInstance = $container->get($className);
            if (!$classInstance|| !$classInstance instanceof $name) {
                throw new \RuntimeException(sprintf('class %s must be instanceof of %s',$className, $name));
            }
            return $classInstance;
        });
        
    }
    
    protected function getRouterDefinition()
    {
        echo "aaa";
        $routerConfig = $this['router'];
        if (!$routerConfig['enabled'])
        {
            return;
        }
        $routerConfig['routers'] = (array)$routerConfig['routers'];
        $routerConfig['rules'] = (array)$routerConfig['rules'];
        
        return new CallableDefinition(Router::class, function(Request $request, Environment $env) use ($routerConfig) {
            $routerInstance = new Router($request, $env->isConsole());

            // 注册路由
            foreach ($routerConfig['routers'] as $routerName => $routerclass)
            {
                $routerInstance->regDriver($routerName, $routerclass);
            }
            
            // 注册路由规则
            foreach ($routerConfig['rules'] as $rule)
            {
                $routerInstance->addRule((array)$rule);
            }
            return $routerInstance;
        });
    }
}

?>