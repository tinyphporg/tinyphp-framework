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
use Tiny\MVC\Application\Properties;

/**
 * 模块实例
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
     * 模块的所有配置
     *
     * @var array
     */
    protected $setting;
    
    /**
     * 初始化构造
     *
     * @param array $config
     */
    public function __construct(ModuleManager $moduleManager, Properties $properties, array $mconfig = [])
    {
        $this->setting = $mconfig;
        $this->modules = $moduleManager;
        $this->profile = new Configuration(null, $mconfig['profile']);
        
        if ($mconfig['config']) {
            $this->config = new Configuration(null, $mconfig['config']);
        }
        if ($mconfig['lang']) {
            $this->lang = new Lang(null, $mconfig['lang']);
            $this->lang->setLocale($properties['lang.locale']);
        }
        $this->version = $mconfig['version'];
    }
    
    /**
     * 获取模块名
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->setting['name'];
    }
    
    /**
     * 获取模块的版本
     *
     * @return mixed
     */
    public function getVersion()
    {
        return $this->setting['version'];
    }
    
    /**
     * 获取模块所在目录的根路径
     *
     * @return mixed
     */
    public function getBaseDir()
    {
        return $this->setting['path'];
    }
    
    /**
     * 是否已经禁用该模块
     *
     * @return boolean
     */
    public function isDisabled()
    {
        return (bool)$this->setting['disabled'];
    }
    
    /**
     * 是否启用该模块
     *
     * @return boolean
     */
    public function isActivated()
    {
        return (bool)$this->setting['activated'];
    }
    
    /**
     * 是否在BeginRequest事件中即进行初始化
     *
     * @return boolean
     */
    public function isInited()
    {
        return (bool)$this->setting['inited'];
    }
    
    /**
     *
     * @return mixed
     */
    public function getControllerNamespace()
    {
        return $this->setting['namespace']['controller'];
    }
    
    /**
     * 获取视图路径
     *
     * @return string
     */
    public function getViewPath()
    {
        return $this->setting['path']['view'];
    }
    
    /**
     * 获取模块的状态信息
     *
     * @return mixed
     */
    public function getStatus()
    {
        return $this->setting['status'];
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($moduleName)
    {
        return key_exists($moduleName, $this->setting);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($moduleName)
    {
        return $this->setting[$moduleName];
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($moduleName, $value)
    {
        throw new ModuleException('Module.setting is read-only and cannot be deleted or reset!');
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($moduleName)
    {
        throw new ModuleException('Module.setting is read-only and cannot be deleted or reset!');
    }
}
?>