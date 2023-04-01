<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ModuleSource.php
 * @author King
 * @version stable 2.0
 * @Date 2022年8月16日下午3:50:29
 * @Class List class
 * @Function List function_container
 * @History King 2022年8月16日下午3:50:29 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Module\Source;

use Tiny\MVC\Module\ModuleManager;
use Tiny\DI\ContainerInterface;
use Tiny\MVC\Module\Parser\ModuleParser;
use Tiny\Runtime\RuntimeCache;

/**
 * 模块提供源
 *
 * @package namespace
 * @since 2022年8月16日下午3:50:56
 * @final 2022年8月16日下午3:50:56
 */
class ModuleSource
{
    
    /**
     * 应用缓存的KEY
     *
     * @var string
     */
    const APPLICATION_MODULE_CACHE_KEY = 'application.module';
    
    /**
     * 当前容器实例
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 当前模块管理器实例
     *
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * 当前Application的应用实例
     *
     * @var RuntimeCache
     */
    protected $cache;
    
    /**
     * 模块搜索后的配置文件数组
     *
     * @var array
     */
    protected $moduleProfiles = [];
    
    /**
     * 是否更新
     *
     * @var boolean
     */
    protected $isUpdated = false;
    
    /**
     *
     * @param ModuleManager $moduleManager
     * @param RuntimeCache $cache
     */
    public function __construct(ContainerInterface $container, ModuleManager $moduleManager, RuntimeCache $cache)
    {
        $this->container = $container;
        $this->cache = $cache;
        $this->moduleManager = $moduleManager;
    }
    
    /**
     * 保存模块配置数组到应用缓存
     *
     * @param array $modules
     * @return boolean
     */
    public function saveToCache($modules, $isUpdated = false)
    {
        if (!$isUpdated || !$this->isUpdated) {
            return;
        }
        return $this->cache->set(self::APPLICATION_MODULE_CACHE_KEY, $modules);
    }
    
    /**
     * 根据路径获取所有的模块配置数组
     *
     * @param string|array $path
     * @return void|array
     */
    public function readFrom($path, $readFromCache = true, array $disabledModules = [])
    {
        $modules = [];
        
        // from cache
        if ($readFromCache && $modules = $this->readFromCache()) {
            return $modules;
        }
        if (!$path) {
            return;
        }
        $profiles = $this->searchModuleProfile($path);
        if (!$profiles) {
            return;
        }
        
        // module parser
        $moduleParser = $this->container->get(ModuleParser::class);
        
        // parse profile
        $modules = [];
        foreach ($profiles as $profile) {
            $module = $moduleParser->parse($profile);
            if (!$module) {
                continue;
            }
            $modules[$module['name']] = $module;
        }
        
        // 解析依赖树
        foreach ($modules as &$module) {
            if (in_array($module['name'], $disabledModules)) {
                $module['disbaled'] = true; 
            }
            $module['requires'] = $moduleParser->parseModuleRequires($module['name'], $module['requires'], $modules);
        }
        
        $this->isUpdated = true;
        return $modules;
    }
    
    /**
     * 从应用缓存读取模块的配置数组
     *
     * @return array
     */
    protected function readFromCache()
    {
        return (array)$this->cache->get(self::APPLICATION_MODULE_CACHE_KEY);
    }
    
    /**
     * 从路径搜索模块配置文件
     *
     * @param string $path
     * @return []
     */
    protected function searchModuleProfile($path)
    {
        // define search paths
        function searchpath($path, &$profiles, $isChildNode = true)
        {
            if (is_array($path)) {
                foreach ($path as $p) {
                    searchpath($p, $profiles);
                }
                return;
            }
            
            // 目录扫描
            if (is_dir($path)) {
                $configPath = $path . '/module.json';
                if (is_file($configPath)) {
                    return $profiles[] = $configPath;
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
                        searchpath($childPath, $profiles, false);
                    }
                }
                return;
            }
            
            // 配置文件，即判断文件名是否为module.json
            if (is_file($path) && basename($path) == 'module.json') {
                $profiles[] = $paths;
            }
        }
        
        // search paths
        $profiles = [];
        searchpath($path, $profiles);
        return $profiles;
    }
}
?>