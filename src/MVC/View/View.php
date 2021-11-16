<?php
/**
 *
 * @Copyright (C), 2011-, King.$i
 * @Name  View.php
 * @Author  King
 * @Version  Beta 1.0
 * @Date: Mon Dec 12 01:34 00 CST 2011
 * @Description
 * @Class List
 *  	1.
 *  @Function List
 *   1.
 *  @History
 *      <author>    <time>                        <version >   <desc>
 *        King      Mon Dec 12 01:34:00 CST 2011  Beta 1.0           第一次建立该文件
 *        King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\View;

use Tiny\MVC\View\Engine\IEngine;
use Tiny\MVC\ApplicationBase;

/**
 * 视图层
 *
 * @package Tiny.Application.Viewer
 * @since : Mon Dec 12 01:15 51 CST 2011
 * @final : Mon Dec 12 01:15 51 CST 2011
 */
class View implements \ArrayAccess
{

    /**
     * View当前实例
     *
     * @var View
     */
    protected static $_instance;

    /**
     * 当前application实例
     *
     * @var ApplicationBase
     */
    protected $_app;
    
    /**
     * 视图引擎的配置策略数组
     *
     * @var array key 引擎类名
     *      value string 为支持解析的模板文件扩展名
     *      value array 为支持解析的模板文件扩展名数组
     */
    protected $_engines = [
        '\Tiny\MVC\View\Engine\PHP' => ['ext' => ['php'], 'config' => [], 'instance' => NULL],
        '\Tiny\MVC\View\Engine\Smarty' => ['ext' => ['tpl'], 'config' => [], 'instance' => NULL],
        '\Tiny\MVC\View\Engine\Template' => ['ext' => ['htm', 'html'], 'config' => [], 'instance' => NULL],
        '\Tiny\MVC\View\Engine\MarkDown' => [ 'ext' => ['md'], 'config' => [], 'instance' => NULL]
    ];

    /**
     * 视图助手的配置策略数组
     *
     * @var array
     */
    protected $_helpers = [
        '\Tiny\MVC\View\Helper\HelperList' => ['config' => [], 'instance' => NULL],
        '\Tiny\MVC\View\Helper\MessageBox' => ['config' => [], 'instance' => NULL]
    ];



    /**
     * 视图层预设的值
     *
     * @var array
     */
    protected $_variables = [];

    /**
     * 各种视图引擎配置
     *
     * @var array
     */
    protected $_viewConfig = [];

    /**
     * 模板文件夹
     *
     * @var string|array
     */
    protected $_templateDir = '';

    /**
     * 模板编译存放文件夹
     *
     * @var string
     */
    protected $_compileDir = '';

    /**
     * 已解析的模板文件列表
     *
     * @var array
     */
    protected $_templateList = [];

    /**
     * 是否开启模板缓存
     *
     * @var boolean
     */
    protected $_cacheEnabled = FALSE;

    /**
     * 模板缓存路径
     *
     * @var string
     */
    protected $_cacheDir = '';

    /**
     * 模板缓存时间
     *
     * @var integer
     */
    protected $_cacheLifetime = 120;

    /**
     * 获取当前视图单一实例
     *
     * @return View
     */
    public static function getInstance()
    {
        if (!self::$_instance)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 设置 当前的application实例
     *
     * @param ApplicationBase $app 当前的application实例
     */
    public function setApplication(ApplicationBase $app)
    {
        $this->_app = $app;
    }
    
    /**
     * 通过扩展名绑定视图处理引擎
     *
     * @param array $econfig
     * @return bool
     */
    public function bindEngine($econfig)
    {
        if (!is_array($econfig))
        {
            return FALSE;
        }
        
        // engine 必须为string类型
        if (!key_exists('engine', $econfig) || !is_string($econfig['engine']))
        {
            return FALSE;
        }

        $engineName = $econfig['engine'];
        $config = is_array($econfig['config']) ? $econfig['config'] : [];
        $ext = is_array($econfig['ext']) ? $econfig['ext'] : [(string)$econfig['ext']];
        $ext = array_map('strtolower', $ext);
        
        // 不存在新建
        if (!key_exists($engineName, $this->_engines))
        {
            $this->_engines[$engineName] = ['engine' => $engineName, 'config' => $config, 'ext' => $ext, 'instance' => NULL];
            return TRUE;
        }
        
        // 存在类似配置 则合并
        $enginePolicy = & $this->_engines[$engineName];
        $enginePolicy['config'] = array_merge($enginePolicy['config'], $config);
        $enginePolicy['ext'] = array_merge($enginePolicy['ext'], $ext);
        
        // 补全
        if (!isset($enginePolicy['engine']))
        {
            $enginePolicy['engine'] = $engineName;
        }
        if (!isset($enginePolicy['instance']))
        {
            $enginePolicy['instance'] = NULL;
        }
        return TRUE;
    }

    /**
     * 通过扩展名绑定视图助手
     *
     * @param mixed $hconfig
     *            助手配置
     * @return bool
     */
    public function bindHelper($hconfig)
    {
        if (!is_array($hconfig))
        {
            return FALSE;
        }
        
        // helper助手名必须配置
        if (!key_exists('helper', $hconfig) || !is_string($hconfig['helper']))
        {
            return FALSE;
        }

        $helperName = $hconfig['helper'];
        $config = is_array($hconfig['config']) ? $hconfig['config'] : [];
        
        // 不存在新建
        if (!key_exists($helperName, $this->_helpers))
        {
            $this->_helpers[$helperName] = ['helper' => $helperName, 'config' => $config, 'instance' => NULL];
            return TRUE;
        }

        // 存在则合并
        $helperPolicy = & $this->_helpers[$helperName];
        $helperPolicy['config'] = array_merge($helperPolicy['config'], $config);
        if (!isset($helperPolicy['helper']))
        {
            $helperPolicy['helper'] = $helperName;
        }
        if (!isset($helperPolicy['instance']))
        {
            $helperPolicy['instance'] = NULL;
        }
        return TRUE;
    }

    /**
     * 通过模板路径的文件扩展名 获取视图引擎的类名
     *
     * @param string $ext 模板路径的文件扩展名
     * @return FALSE | string 
     */
    public function getEngineNameByExt($ext)
    {
        $econfig = $this->_getEngineConfigByExt($ext);
        if (!$econfig)
        {
            return FALSE;
        }
        return $econfig['engine'];
    }

    /**
     * 获取模板文件所在目录
     *
     * @return string
     */
    public function getTemplateDir()
    {
        return $this->_templateDir;
    }

    /**
     * 获取解析过的模板文件
     *
     * @return array
     */
    public function getTemplateList()
    {
        return $this->_templateList;
    }
    
    
    /**
     * 增加一条视图解析记录
     * 
     * @param string $templatePath 模板相对路径
     * @param string $templateRealPath 模板真实路径
     * @param string $ename 模板引擎名
     * @param IEngine $engineInstance 模板引擎实例
     */
    public function addTemplateList($templatePath, $templateRealPath, $engineInstance)
    {
        $this->_templateList[] = [$templatePath, $templateRealPath, get_class($engineInstance), $engineInstance];
    }

    /**
     * 设置模板文件所在目录
     *
     * @param string $path
     *            模板文件所在目录路径
     * @return View
     */
    public function setTemplateDir($path)
    {
        $this->_templateDir = $path;
        return $this;
    }

    /**
     * 设置模板编译存放的目录
     *
     * @param string $path
     *            编译后的文件存放目录路径
     * @return View
     */
    public function setCompileDir($path)
    {
        $this->_compileDir = $path;
        return $this;
    }

    /**
     * 获取模板文件编译后所在目录
     *
     * @return string
     */
    public function getCompileDir()
    {
        return $this->_compileDir;
    }

    /**
     * 获取预编译变量
     *
     * @return array
     */
    public function getAssigns()
    {
        return $this->_variables;
    }

    /**
     * 添加一个或多个视图变量
     *
     * @param string|array $key
     *            当key为数组时，可添加多个预编译变量
     * @return View
     */
    public function assign($key, $value = null)
    {
        if (is_array($key))
        {
            $this->_variables = array_merge($this->_variables, $key);
        }
        else
        {
            $this->_variables[$key] = $value;
        }
        return $this;
    }

    /**
     * 解析模板，并将解析后的模板内容注入到application的response中
     *
     * @param string $tpath  模板路径
     * @param boolean $assign 额外的assign变量 仅本次解析生效
     * @param boolean $isAbsolute 模板路径是否为绝对路径
     * @return void
     */
    public function display($tpath, $assign = FALSE, $isAbsolute = FALSE)
    {   
        $content = $this->getEngineByPath($tpath)->fetch($tpath, $assign, $isAbsolute);
        $this->_app->response->appendBody($content);
    }

    /**
     * 解析模板，并获取解析后的字符串
     *
     * @param string $tpath 模板路径
     * @param boolean $assign 额外的assign变量 仅本次解析生效
     * @param boolean $isAbsolute 是否为绝对的模板路径
     * @return string
     */
    public function fetch($tpath, $assign = FALSE, $isAbsolute = FALSE)
    {
        return $this->getEngineByPath($tpath)->fetch($tpath, $assign, $isAbsolute);
    }

    /**
     * 通过模板的文件路径获取绑定的视图模板引擎实例
     *
     * @param string $filepath 模板文件路径
     * @return IEngine
     */
    public function getEngineByPath($templatePath)
    {
        $ext = pathinfo($templatePath, PATHINFO_EXTENSION);        
        $econfig = $this->_getEngineConfigByExt($ext);
        if (!$econfig)
        {
            throw new ViewException(sprintf('Viewer error: ext"' . $ext . '"is not bind', $templatePath));
        } 
        
        $engineInstance = $this->_getEngineInstanceByConfig($econfig);
        return $engineInstance;
    }

    /**
     * 获取变量值
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($key)
    {
        return $this->_variables[$key];
    }

    /**
     * 设置变量
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($key, $value)
    {
        $this->_variables[$key] = $value;
    }

    /**
     * 变量是否存在
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($key)
    {
        return key_exists($key, $this->_variables);
    }

    /**
     * 删除变量
     * 
     * {@inheritDoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($key)
    {
        unset($this->_variables[$key]);
    }

    /**
     * 设置模板缓存
     *
     * @param string $cacheDir 模板缓存存放文件夹
     * @param int $cacheLifetime 模板缓存时间 <=0时不开启cache >0时开启缓存
     * @return boolean 是否开启
     */
    public function setCache($cacheDir, int $cacheLifetime = 120)
    {
        if ($cacheLifetime < 0)
        {
            return $this->_clearCache();
        }
        
        $this->_cacheLifetime = $cacheLifetime;
        $this->_cacheDir = $cacheDir;
        $this->_cacheEnabled = TRUE;
        return $this->_cacheEnabled;
    }
    
    /**
     * 惰性加载 视图助手的实例作为视图层的成员变量
     *
     * @param string $helperName 助手类名
     * @return IHelper
     */
    public function __get($helperName)
    {
        // 助手名必须以字母开头
        if (!preg_match("/[a-z][a-z0-9_]+/i", $helperName))
        {
            return NULL;
        }
        
        // 获取助手实例
        $helperInstance = $this->_getMatchedHelper($helperName);
        if (!$helperInstance)
        {
            throw new ViewException('该变量不存在，或不是实现了IHelper接口的视图助手实例');
        }
        
        $this->{$helperName} = $helperInstance;
        return $helperInstance;
    }

    /**
     * 初始化视图层
     *
     * @return void
     */
    protected function __construct()
    {
        // 将自身注入视图变量
        $this->_variables['view'] = $this;
    }

    /**
     *  获取匹配的助手实例 
     * @param string $helperName
     * @return IHelper|FALSE  FALSE 获取失败
     */
    protected function _getMatchedHelper($helperName)
    {
        // 倒序查找助手配置
        $helpers = array_reverse($this->_helpers);
        foreach ($helpers as $hname => $hconfig)
        {
            $instance = $this->_getHelperInstance($hname);
            $matchRet = $instance->matchHelperByName($helperName);
            if ($matchRet)
            {
                return ($matchRet instanceof IHelper) ? $matchRet : $instance;
            }
        }
        return FALSE;
    }

    /**
     * 获取助手实例
     *
     * @param array $hconfig
     *            助手配置
     * @return IHelper
     */
    protected function _getHelperInstance($helperName)
    {
        $hconfig = & $this->_helpers[$helperName];
        if ($hconfig['instance'])
        {
            return $hconfig['instance'];
        }
        
        if ($helperName != $hconfig['helper'])
        {
            $hconfig['helper'] = $helperName;
        }
        
        if (!class_exists($helperName))
        {
            throw new ViewException(sprintf('class "%s" is not exists', $helperName));
        }
        
        // 实例
        $helperInstance = new $helperName();
        if (!$helperInstance instanceof IHelper)
        {
            throw new ViewException(sprintf('class "%s" is not instanceof \Tiny\MVC\View\Helper\IHelper', $helperName));
        }
        $hconfig['instance'] = $helperInstance;
        
        // 注入视图实例和配置
        $helperInstance->setViewHelperConfig($this, $hconfig['config']);
        return $helperInstance;
    }

    /**
     * 根据模板路径的文件扩展名获取引擎配置
     *
     * @param string $ext 模板文件扩展名
     * @return array | void
     */
    protected function _getEngineConfigByExt($ext)
    {        
        // 扩展名向前覆盖
        $enginePolicys = array_reverse($this->_engines);
        
        $ext = strtolower($ext);
        foreach ($enginePolicys as $ename => $econfig)
        {
            if (!in_array($ext, $econfig['ext']))
            {
                continue;
            }
            if (!isset($econfig['engine']))
            {
                $econfig['engine'] = $ename;
            }
            return $econfig;
        }
    }

    /**
     * 根据配置获取视图引擎实例
     *
     * @param array $econfig 视图引擎配置
     * @return IEngine
     */
    protected function _getEngineInstanceByConfig($econfig)
    {
        if ($econfig['instance'])
        {
            // 每次调用视图引擎实例 刷新注入的视图变量
            $econfig['instance']->assign($this->_variables);
            return $econfig['instance'];
        }
        
        $engineName = (string)$econfig['engine'];
        if (!class_exists($engineName))
        {
            throw new ViewException(sprintf('class "%s" is not exists', $engineName));
        }
        
        $engineInstance = new $engineName();
        
        // 注入视图实例和引擎配置数组
        $engineInstance->setViewEngineConfig($this, $econfig['config']);
        if (!$engineInstance instanceof IEngine)
        {
            throw new ViewException(sprintf('class "%s" is not instanceof \Tiny\MVC\View\Engine\IEngine', $engineName));
        }
        
        // 设置初始化的路径参数
        $engineInstance->setTemplateDir($this->_templateDir);
        $engineInstance->setCompileDir($this->_compileDir);
        
        // 设置是否缓存
        if ($this->_cacheEnabled)
        {
            $engineInstance->setCache($this->cacheDir, $this->cacheLifetime);
        }
        
        // 注入预设变量
        $engineInstance->assign($this->_variables);
        $this->_engines[$engineName]['instance'] = $engineInstance;
        return $engineInstance;
    }
    
    /**
     * 清空缓存参数
     *
     * @return boolean
     */
    protected function _clearCache()
    {
        $this->_cacheEnabled = FALSE;
        $this->_cacheLifetime = 0;
        $this->_cacheDir = '';
        return $this->_cacheEnabled;
    }
}
?>