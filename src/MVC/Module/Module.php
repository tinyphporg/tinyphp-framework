<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name Module.php
 * @author King
 * @version stable 2.0
 * @Date 2022年4月11日上午10:41:54
 * @Class List class
 * @Function List function_container
 * @History King 2022年4月11日上午10:41:54 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Module;

use Tiny\Lang\Lang;
use Tiny\Config\Configuration;

/**
* 模块
* 
* @package Tiny.MVC.Module
* @since 2022年4月11日上午10:49:30
* @final 2022年4月11日上午10:49:30
*/
class Module implements \ArrayAccess
{
    /**
     * 模块名称
     * 
     * @var string
     */
    public $name;
    
    /**
     * 配置文件
     * 
     * @var Configuration
     */
    public $profile;
    
    /**
     * 是否在模块管理器初始化时即启用并运行
     * 
     * @var boolean
     */
    public $isInited = false;
    
    /**
     * 模块版本号
     *  
     * @var string
     */
    public $version = 'dev';

    /**
     * 模块的语言包实例
     * 
     * @var Lang
     */
    public $lang;
    
    /**
     * 模块的配置实例
     * 
     * @var Configuration
     */
    public $config;
    
    /**
     * 模块的视图配置路径
     * 
     * @var string
     */
    public $viewPath;
    
    /**
     * 模块管理器实例
     * 
     * @var ModuleManager
     */
    public $modules;
    
    /**
     * 模块的数据
     * 
     * @var array
     */
    protected $moduleConfig;
    
    /**
     * 初始化构造
     * @param array $config
     */
    public function __construct(ModuleManager $moduleManager, array $mconfig  = [])
    {
        $this->moduleConfig = $mconfig;
        $this->modules = $moduleManager;
        $this->viewPath = $mconfig['path']['view'];
        $this->profile = new Configuration(null, $mconfig['profile']);
        
        if ($mconfig['config']) {
            $this->config = new Configuration(null, $mconfig['config']);
        }
        if ($mconfig['lang']) {
            $this->lang = new Lang(null, $mconfig['lang']);
        }
        
        $this->name = $mconfig['name'];
        $this->isActivated = (bool)$mconfig['activated'];
        $this->isDisabled = (bool)$mconfig['distabled'];
        $this->path = $mconfig['path'];
        $this->version = $mconfig['version'];
        $this->isInited = (bool)$mconfig['init'];
        $this->namespace = $mconfig['namespace'];
        $this->namespaces = $mconfig['namespaces'];

    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($moduleName) {
        return key_exists($moduleName, $this->moduleConfig);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($moduleName) {
        return $this->moduleConfig[$moduleName];
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($moduleName, $value) 
    {
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($moduleName) 
    {
        
    }
    
    /**
     * 获取配置属性
     * 
     * @param string $key
     * @return NULL|mixed
     */
    public function __get($key)
    {
        return key_exists($key, $this->moduleConfig) ? $this->moduleConfig[$key] : null;        
    }
}
?>