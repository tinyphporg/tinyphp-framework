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

class PropertiesException extends \Exception
{
    
}
class PropertiesDefinition implements DefinitionInterface, SelfResolvingDefinition
{
    
    const DEFINITION_NAME_ALLOW_LIST = ['cache', 'data', 'config', 'lang', 'view'];
    
    /**
     * 应用层 运行时缓存KEY
     * 
     * @var array
     */
    const RUNTIME_CACHE_KEYS = [
        'CONFIG' => 'app.config',
        'LANG' => 'app.lang',
        'MODEL' => 'app.model'
    ];
    
    /**
     * properties 实例
     *
     * @var Properties
     */
    protected $properties;

    protected $name;
    
    /**
     * 应用缓存
     * 
     * @var RuntimeCacheItem
     */
    protected $appCache;
    
    public function __construct(Properties $properties)
    {
        $this->properties = $properties;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function resolve(ContainerInterface $container)
    {
           if (!$this->appCache)
           {
               $this->appCache = $container->get('applicationCache');
           }
           
            switch ($this->name)
            {
                case 'cache':
                    return $this->createCacheInstance();
                case 'data':
                    return $this->createDataInstance();
                case 'config':
                    return $this->createConfigInstance();
                case 'lang':
                    return $this->createLangInstance();
                case 'view':
                    return $this->createViewInstance($container);
                default:
                    return false;
            }
    }

    public function isResolvable(ContainerInterface $container): bool
    {
        return true;
    }

    /**
     * 根据配置创建缓存实例
     * 
     * @return Cache|NULL
     */
    protected function createCacheInstance(): ?Cache
    {
        
        $config = $this->properties['cache'];
        if (! $config['enabled'])
        {
            return null;
        }
        
        // 缓存自定义适配器
        $adapters = (array)$config['storage']['adapters'] ?: [];
        foreach ($adapters as $id => $className)
        {
            Cache::regStorageAdapter($id, $className);
        }
        
        $ttl = (int)$config['ttl'] ?: 60;
        $storageId = (string)$config['storage']['id'] ?: 'default';
        $storagePath = (string)$config['storage']['path'];
        
        $cacheInstance = new Cache();
        $cacheInstance->setDefaultStorageId($storageId);
        $cacheInstance->setDefaultStoragePath($storagePath);
        $cacheInstance->setDefaultTtl($ttl);
        
        $storageConfig = ($config['storage']['config']) ?: [];
        foreach ($storageConfig as $scfg)
        {   
            $options = (array)$scfg['options'] ?: [];
            $cacheInstance->addStorageAdapter($scfg['id'], $scfg['storage'], $options);
        }
        return $cacheInstance;
    }
    
    /**
     * 根据配置创建配置实例
     * 
     * @throws ApplicationException
     * @return \Tiny\Config\Configuration
     */
    protected function createConfigInstance()
    {

        $config = $this->properties['config'];
        if (!$config['enabled'])
        {
            throw new ApplicationException("properties.config.enabled is false!");
        }
        if (!$config['path'])
        {
            throw new ApplicationException("properties.config.path is not allow null!");
        }
        $configInstance = new Configuration($config['path']);
        if ($this->properties['debug.enabled'] || !$config['cache']['enabled'])
        {
            return $configInstance;
        }
        
        $cacheData = $this->getConfigDataFromRuntimeCache();
        if ($cacheData && is_array($cacheData))
        {
            $configInstance->setData($cacheData);
        }
        else
        {
            $data = $configInstance->get();
            $this->saveConfigDataToRuntimeCache($data);
        }
        return $configInstance;
    }
    
    /**
     * 根据配置创建DATA实例
     * 
     * @return Data|NULL
     */
    protected function createDataInstance(): ?Data
    {
        $config = $this->properties['data'];
        if (!$config['enabled'])
        {
            return null;
        }
        
        $config['drivers'] = $config['drivers'] ?: [];
        foreach ($config['drivers'] as $id => $className)
        {
            Data::regDriver($id, $className);
        }
    
        $config['policys'] = $config['policys'] ?: [];
        $config['charset'] = $config['charset'] ?: 'utf8';
        
        $dataInstance = Data::getInstance();
        foreach ($config['policys'] as $policy)
        {
            $policy['def_charset'] = $config['charset'];
            $dataInstance->addPolicy($policy);
        }
        return $dataInstance;
    }
    
    /**
     * 获取语言操作对象
     *
     * @param void
     * @return Lang
     */
    protected function createLangInstance()
    {
        $config = $this->properties['lang'];
        if (!$config['enabled'])
        {
            throw new ApplicationException("properties.lang.enabled is false!");
        }
        $langInstance = Lang::getInstance();
        $langInstance->setLocale($config['locale'])->setPath($config['path']);
        if ($this->properties['debug.enabled']  || !$config['cache']['enabled'])
        {
          return $langInstance;
        }
        
        $langData = $this->getLangDataFromRuntimeCache();
        if ($langData && is_array($langData))
        {
            $langInstance->setData($langData);
        }
        else
        {
            $langData = $langInstance->getData();
            $this->saveLangDataToRuntimeCache($langData);
        }
        return $langInstance;
    }
    
    /**
     * 获取视图类型
     *
     * @return View
     */
    protected function createViewInstance(ContainerInterface $container)
    {
        $config = $this->properties['view'];
        
        $app = $this->properties->app;
        $viewInstance = new View($app);
        
        $helpers = (array)$config['helpers'];
        $engines = (array)$config['engines'];
        
        $assign = (array)$config['assign'] ?: [];
        if ($this->properties['config.enabled'])
        {
            $assign['config'] = $container->get('config');
        }
        $defaultTemplateDirname = TINY_MVC_RESOURCES . 'views/';
        $templateDirs = [$defaultTemplateDirname];
        $templateDirname = $config['template_dirname'] ?: 'default';
        $templateDirs[] = $config['src'] . $templateDirname . DIRECTORY_SEPARATOR;
        
        // composer require tinyphp-ui;
        $uiconfig = $config['ui'];
        if ($uiconfig['enabled'])
        {
            $uiHelperName = (string)$uiconfig['helper'];
            if ($uiHelperName)
            {
                $helpers[] = [ 'helper' => $uiHelperName];
            }
            $templatePlugin = (string)$uiconfig['template_plugin'];
            if($templatePlugin)
            {
                $uiPluginConfig = [
                    'public_path' => $config['ui']['public_path'],
                    'inject' => $config['ui']['inject'],
                    'dev_enabled' => $config['ui']['dev_enabled'],
                    'dev_public_path' => $config['ui']['dev_public_path']
                ];
                $engines[] = ['engine' => '\Tiny\MVC\View\Engine\Template', 'config' => ['plugins' => [['plugin' => $templatePlugin, 'config' => $uiPluginConfig]]] ];
            }
            if ($uiconfig['template_dirname'])
            {
                $templateDirs[] = (string)$uiconfig['template_dirname'];
            }
        }
        
        if ($this->properties['lang.enabled'])
        {
            $assign['lang'] = $container->get('lang');
            if ($config['view']['lang']['enabled'] !== FALSE)
            {
                $templateDirs[] = $config['src'] . $this->_prop['lang']['locale'] . DIRECTORY_SEPARATOR;
            }
        }
        
        // 设置模板搜索目录
        $templateDirs = array_reverse($templateDirs);
        $viewInstance->setTemplateDir($templateDirs);
        if ($config['cache'] && $config['cache']['enabled'])
        {
            $viewInstance->setCache($config['cache']['dir'], (int)$config['cache']['lifetime']);
        }
        
        // engine初始化
        foreach ($engines as $econfig)
        {
            $viewInstance->bindEngine($econfig);
        }
        
        //helper初始化
        foreach ($helpers as $econfig)
        {
            $viewInstance->bindHelper($econfig);
        }
        
        $viewInstance->setCompileDir($config['compile']);
        
        $viewInstance->assign($assign);
        return $viewInstance;
    }
    /**
     * 从运行时缓存获取语言包配置数据
     *
     * @return data|FALSE
     */
    protected function getLangDataFromRuntimeCache()
    {
        return $this->getDataFromRuntimeCache(self::RUNTIME_CACHE_KEYS['LANG']);
    }
    
    /**
     * 保存语言包配置数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function saveLangDataToRuntimeCache($data)
    {
        return $this->saveDataToRuntimeCache(self::RUNTIME_CACHE_KEYS['LANG'], $data);
    }
    
    /**
     * 从运行时缓存获取配置数据
     *
     * @return data|FALSE
     */
    protected function getConfigDataFromRuntimeCache()
    {
        return $this->getDataFromRuntimeCache(self::RUNTIME_CACHE_KEYS['CONFIG']);
    }
    
    /**
     * 保存配置数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function saveConfigDataToRuntimeCache($data)
    {
        return $this->saveDataToRuntimeCache(self::RUNTIME_CACHE_KEYS['CONFIG'], $data);
    }
    
    /**
     * 从运行时缓存获取数据
     *
     * @return data|FALSE
     */
    protected function getDataFromRuntimeCache($key)
    {
        if (!$this->appCache)
        {
            return FALSE;
        }
        $data = $this->appCache->get($key);
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
    protected function saveDataToRuntimeCache($key, $data)
    {
        if (!$this->appCache)
        {
            return FALSE;
        }
        return $this->appCache->set($key, $data);
    }
}

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
     * 注解链
     *
     * @var array
     */
    protected $definitionProviderChain = [];
    

    protected $propertiesDefinitions = [];
    
    protected $definitionSourceArray = [
        View::class
    ];
    
    public $app;
    
    public $namespace = 'App';
    
    public $controllerNamespace = 'Controlller';
    
    public $modelNameSpace = 'Model';

    public function __construct($cpath, ApplicationBase $app)
    {
        parent::__construct($cpath);
        $this->app = $app;
        $this->init();
        $this->initPath();
        $this->initDebug();
        //$this->definitionProviderChain[] = new DefintionProivder($this['container.config_path'], $this->definitionSourceArray);
    }
    
    /**
     * 获取容器定义实例
     * 
     */
    public function getDefinition($name)
    {
        if (!in_array($name, PropertiesDefinition::DEFINITION_NAME_ALLOW_LIST))
        {
            return false;
        }
        if (! key_exists($name, $this->propertiesDefinitions))
        {
            $definition = new PropertiesDefinition($this);
            $definition->setName($name);
            $this->propertiesDefinitions[$name] = $definition;
        }
        return $this->propertiesDefinitions[$name];
    }

    /**
     * 获取所有的容器定义实例
     * 
     * @return array
     */
    public function getDefinitions(): array
    {
        $names = PropertiesDefinition::DEFINITION_NAME_ALLOW_LIST;
        foreach($names as $name)
        {
            if (!in_array($name, $this->propertiesDefinitions))
            {
                $this->getDefinition($name);
            }
        }
        return $this->propertiesDefinitions;
    }
    
    protected function init()
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
        $controllerNamespace =  ($this->app instanceof ConsoleApplication) ? $cnamespace['console'] : $cnamespace['default'];  
        $controllerNamespace = ((string)$controllerNamespace ?: $this->controllerNamespace);
        $this->controllerNamespace = '\\' . $this->namespace . '\\' . ((string)$controllerNamespace ?: $this->controllerNamespace);
        
        // model namespace
        $modelNameSpace = (string)$this->properties['model.namespace'] ?: $this->modelNameSpace;
        $this->modelNameSpace = '\\' . $this->namespace . '\\' . $modelNameSpace;  
    }
    
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
    
    protected function initDebug()
    {
        $debugConfig = $this->get('debug');
        if ($debugConfig['enabled'] && $debugConfig['plugin'])
        {
            $this->set('plugins.debug', $debugConfig['plugin']);
        }
    }
}

?>