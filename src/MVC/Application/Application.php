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
use Tiny\DI\DefinitionProviderInterface;
use Tiny\MVC\ApplicationBase;
use Tiny\Cache\Cache;
use Tiny\DI\ContainerInterface;
use Tiny\DI\DefintionProivder;
use Tiny\DI\CallableDefinition;
use Tiny\DI\DefinitionInterface;
use Tiny\DI\SelfResolvingDefinition;
use Tiny\Data\Data;
use Tiny\Runtime\RuntimeCacheItem;
use Tiny\MVC\ApplicationException;
use Tiny\Lang\Lang;
use Tiny\MVC\View\View;

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
                case 'view':
                    return $this->createViewInstance();
                case 'data':
                    return $this->createDataInstance();
                case 'config':
                    return $this->createConfigInstance();
                case 'lang':
                    return $this->createLangInstance();
                case 'view':
                    return $this->createViewInstance();
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
    protected function createViewInstance()
    {
        $config = $this->properties['view'];
        
        $viewInstance = View::getInstance();
        $viewInstance->setApplication($this->properties->app);
        
        $helpers = (array)$prop['helpers'];
        $engines = (array)$prop['engines'];
        
        $assign = (array)$prop['assign'] ?: [];
        $assign['env'] = $this->runtime->env;
        $assign['request'] = $this->request;
        $assign['response'] = $this->response;
        $assign['config'] = $this->getConfig();
        
        $defaultTemplateDirname = TINY_MVC_RESOURCES . 'views/';
        $templateDirs = [$defaultTemplateDirname];
        $templateDirname = $prop['template_dirname'] ?: 'default';
        $templateDirs[] = $prop['src'] . $templateDirname . DIRECTORY_SEPARATOR;
        
        // composer require tinyphp-ui;
        $uiconfig = $prop['ui'];
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
                    'public_path' => $prop['ui']['public_path'],
                    'inject' => $prop['ui']['inject'],
                    'dev_enabled' => $prop['ui']['dev_enabled'],
                    'dev_public_path' => $prop['ui']['dev_public_path']
                ];
                $engines[] = ['engine' => '\Tiny\MVC\View\Engine\Template', 'config' => ['plugins' => [['plugin' => $templatePlugin, 'config' => $uiPluginConfig]]] ];
            }
            if ($uiconfig['template_dirname'])
            {
                $templateDirs[] = (string)$uiconfig['template_dirname'];
            }
        }
        
        if ($this->_prop['lang']['enabled'])
        {
            $assign['lang'] = $this->getLang();
            if ($prop['view']['lang']['enabled'] !== FALSE)
            {
                $templateDirs[] = $prop['src'] . $this->_prop['lang']['locale'] . DIRECTORY_SEPARATOR;
            }
        }
        
        // 设置模板搜索目录
        $templateDirs = array_reverse($templateDirs);
        $this->_view->setTemplateDir($templateDirs);
        if ($prop['cache'] && $prop['cache']['enabled'])
        {
            $this->_view->setCache($prop['cache']['dir'], (int)$prop['cache']['lifetime']);
        }
        
        // engine初始化
        foreach ($engines as $econfig)
        {
            $this->_view->bindEngine($econfig);
        }
        
        //helper初始化
        foreach ($helpers as $econfig)
        {
            $this->_view->bindHelper($econfig);
        }
        
        $this->_view->setCompileDir($prop['compile']);
        
        $this->_view->assign($assign);
        return $this->_view;
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
    
    public $app;

    public function __construct($cpath, ApplicationBase $app)
    {
        parent::__construct($cpath);
        $this->app = $app;

        $this->initPath();
        $this->initDebug();
        // $this->definitionProviderChain[] = new DefintionProivder($this['container.config_path']);
    }
    
    /**
     * 获取容器定义实例
     * 
     * @see \Tiny\DI\DefinitionProviderInterface::getDefinition()
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
    
    protected function initPath()
    {
        $appPath = $this->_app->path;
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
            $rpath = preg_replace("#/+#", '/', $appPath . $path);
            $this->set($p, $rpath);
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
}

?>