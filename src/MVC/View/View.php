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
 *        King 2020年6月1日14:21 stable 1.0.01 审定
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
     * 引擎策略数组
     *
     * @var array key 引擎类名
     *      value string 为支持解析的模板文件扩展名
     *      value array 为支持解析的模板文件扩展名数组
     */
    protected $_engines = [
        '\Tiny\MVC\View\Engine\PHP' => [
            'ext' => [
                'php'
            ],
            'config' => [],
            'instance' => NULL
        ],
        '\Tiny\MVC\View\Engine\Smarty' => [
            'ext' => [
                'tpl'
            ],
            'config' => [],
            'instance' => NULL
        ],
        '\Tiny\MVC\View\Engine\Template' => [
            'ext' => [
                'htm',
                'html'
            ],
            'config' => [],
            'instance' => NULL
        ]
    ];

    /**
     * 助手实例
     *
     * @var array
     */
    protected $_helpers = [
        '\Tiny\MVC\View\Helper\HelperList' => [
            'config' => [],
            'instance' => NULL
        ],
        '\Tiny\MVC\View\Helper\MessageBox' => [
            'config' => [],
            'instance' => NULL
        ]
    ];

    /**
     * 当前application实例
     *
     * @var ApplicationBase
     */
    protected $_app;

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
    protected $_templateFiles = [];

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
        if (!key_exists('engine', $econfig) || !is_string($econfig['engine']))
        {
            return FALSE;
        }

        $engineName = $econfig['engine'];
        $config = is_array($econfig['config']) ? $econfig['config'] : [];
        $ext = is_array($econfig['ext']) ? $econfig['ext'] : [
            (string)$econfig['ext']
        ];
        $ext = array_map('strtolower', $ext);
        if (!key_exists($engineName, $this->_engines))
        {
            $this->_engines[$engineName] = [
                'engine' => $engineName,
                'config' => $config,
                'ext' => $ext,
                'instance' => NULL
            ];
            return TRUE;
        }

        $enginePolicy = &$this->_engines[$engineName];
        $enginePolicy['config'] = array_merge($enginePolicy['config'], $config);
        $enginePolicy['ext'] = array_merge($enginePolicy['ext'], $ext);
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
        if (!key_exists('helper', $hconfig) || !is_string($hconfig['helper']))
        {
            return FALSE;
        }

        $helperName = $hconfig['helper'];
        $config = is_array($hconfig['config']) ? $hconfig['config'] : [];
        if (!key_exists($helperName, $this->_helpers))
        {
            $this->_helpers[$helperName] = [
                'helper' => $helperName,
                'config' => $config,
                'instance' => NULL
            ];
            return TRUE;
        }

        $helperPolicy = &$this->_helpers[$helperName];
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
     * 根据扩展名获取视图处理引擎的类名
     *
     * @param string $ext
     * @return string
     */
    public function getEngineNameByExt($ext)
    {
        $econfig = $this->_getEngineConfigByExt($ext);
        if (!$econfig)
        {
            return;
        }
        return $econfig['engine'];
    }

    /**
     *
     * @param ApplicationBase $app
     */
    public function setApplication(ApplicationBase $app)
    {
        $this->_app = $app;
    }

    /**
     * 添加一个助手
     *
     * @param string $helperName
     *            助手类名
     */
    public function addHelper($helperName)
    {
        if (key_exists($helperName, $this->_helpers))
        {
            return FALSE;
        }
        $this->_helpers[$helperName] = NULL;
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
    public function getTemplateFiles()
    {
        return $this->_templateFiles;
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
            $this->_variables += $key;
        }
        else
        {
            $this->_variables[$key] = $value;
        }
        return $this;
    }

    /**
     * 将解析末班的内容注入到application的response中
     *
     * @param string $tpath
     * @param boolean $assign
     *            额外的assign变量 仅本次解析生效
     * @param boolean $isAbsolute
     *            是否为绝对的模板路径
     * @return void
     */
    public function display($tpath, $assign = FALSE, $isAbsolute = FALSE)
    {
        $this->_templateFiles[] = $tpath;
        $content = $this->getEngineByPath($tpath)->fetch($tpath, $assign, $isAbsolute);
        $this->_app->response->appendBody($content);
    }

    /**
     * 解析视图获取字符串
     *
     * @param string $tpath
     * @param boolean $assign
     *            额外的assign变量 仅本次解析生效
     * @param boolean $isAbsolute
     *            是否为绝对的模板路径
     * @return string
     */
    public function fetch($tpath, $assign = FALSE, $isAbsolute = FALSE)
    {
        $this->_templateFiles[] = $tpath;
        return $this->getEngineByPath($tpath)->fetch($tpath, $assign, $isAbsolute);
    }

    /**
     * 通过模板的文件路径获取绑定的视图模板引擎实例
     *
     * @param string $filepath
     *            视图文件路径
     * @return IEngine
     */
    public function getEngineByPath($templatePath)
    {
        $ext = pathinfo($templatePath, PATHINFO_EXTENSION);
        $econfig = $this->_getEngineConfigByExt($ext);
        if (!$econfig)
        {
            throw new ViewException('Viewer error: ext"' . $ext . '"is not bind');
        }
        return $this->_getEngineInstanceByConfig($econfig);
    }

    /**
     * 实现数组接口之获取元素
     *
     * @param string $key
     *            键
     * @return NULL|string
     */
    public function offsetGet($key)
    {
        return $this->_variables[$key];
    }

    /**
     * 实现数组接口之设置元素
     *
     * @param string $key
     *            键
     * @param mixed $value
     *            值
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->_variables[$key] = $value;
    }

    /**
     * 实现ArrayAccess接口之是否存在元素
     *
     * @param string $key
     *            键
     * @return bool
     */
    public function offsetExists($key)
    {
        return key_exists($key, $this->_variables);
    }

    /**
     * 实现ArrayAccess接口之删除元素
     *
     * @param string $key
     *            键
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->_variables[$key]);
    }

    /**
     * 设置模板缓存参数
     *
     * @param string $cacheDir
     *            模板缓存存放文件夹
     * @param int $cacheLifetime
     *            模板缓存时间 <=0时不开启cache >0时开启缓存
     * @return boolean 是否开启
     */
    public function setCache($cacheDir, int $cacheLifetime = 120)
    {
        if ($cacheLifetime < 0)
        {
            $this->_cacheEnabled = FALSE;
            $this->_cacheLifetime = 0;
            $this->_cacheDir = '';
            return $this->_cacheEnabled;
        }
        $this->_cacheLifetime = $cacheLifetime;
        $this->_cacheDir = $cacheDir;
        $this->_cacheEnabled = TRUE;
        return $this->_cacheEnabled;
    }

    /**
     * 生成url
     *
     * @param array $params
     *            网址参数
     * @param string $mod
     *            生成的url类型
     * @param string $suffix
     *            当$mod = r时的网址后缀
     * @return string
     */
    public function url($params, $mod = 'r', $suffix = '.html')
    {
        return Helper\Url::get($params, $mod, $suffix);
    }

    /**
     * 返回简单的分页样式
     *
     * @return void
     */
    public function splitPage($url, $total, $pageId, $size = 20, $limit = 6, $style = 'def', $color = 'def', $isOut = true)
    {
        $sp = new Helper\SplitPage([
            'url' => $url,
            'total' => $total,
            'size' => $size,
            'pageid' => $pageId,
            'color' => $color,
            'css' => $isOut
        ]);
        return $sp->fetch($style);
    }

    /**
     * 惰性加载视图助手作为成员变量
     *
     * @param string $hname
     *
     * @return IHelper
     */
    public function __get($helperName)
    {
        if (!preg_match("/[a-z][a-z0-9_]+/i", $helperName))
        {
            return NULL;
        }
        $helperInstance = $this->_checkHelper($helperName);
        if (!$helperInstance)
        {
            throw new ViewException('没有预设的IHelper接口的视图助手实例');
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
        $this->_variables['view'] = $this;
    }

    /**
     * 检测是否存在支持对应属性名的helper
     *
     * @param string $helperName
     * @return IHelper|boolean
     */
    protected function _checkHelper($helperName)
    {
        $helpers = array_reverse($this->_helpers);
        foreach ($helpers as $hname => $hconfig)
        {
            $instance = $hconfig['instance'];
            if (!$instance)
            {
                if (!isset($hconfig['helper']))
                {
                    $hconfig['helper'] = $hname;
                    $this->_helpers[$hname]['helper'] = $hname;
                }
                $instance = $this->_getHelperInstance($hconfig);
                $this->_helpers[$hname]['instance'] = $instance;
            }
            if ($instance->checkHelperName($helperName))
            {
                return $instance;
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
    protected function _getHelperInstance($hconfig)
    {
        $helperName = $hconfig['helper'];
        if (!class_exists($helperName))
        {
            throw new ViewException(sprintf('class "%s" is not exists', $helperName));
        }
        $helperInstance = new $helperName();
        if (!$helperInstance instanceof IHelper)
        {
            throw new ViewException(sprintf('class "%s" is not instanceof \Tiny\MVC\View\Helper\IHelper', $helperName));
        }
        $helperInstance->setViewHelper($this, $hconfig['config']);
        return $helperInstance;
    }

    /**
     * 根据扩展名获取引擎配置
     *
     * @param string $ext
     * @return array|void
     */
    protected function _getEngineConfigByExt($ext)
    {
        $ext = strtolower($ext);
        $enginePolicys = array_reverse($this->_engines);
        foreach ($enginePolicys as $ename => $econfig)
        {
            if (in_array($ext, $econfig['ext']))
            {
                if (!isset($econfig['engine']))
                {
                    $econfig['engine'] = $ename;
                }
                return $econfig;
            }
        }
    }

    /**
     * 根据类名获取Viewer实例
     *
     * @param IEngine $className
     *            视图解析类名称
     * @return IEngine
     */
    protected function _getEngineInstanceByConfig($econfig)
    {
        if ($econfig['instance'])
        {
            $engineInstance = $econfig['instance'];
            $engineInstance->assign($this->_variables);
            return $engineInstance;
        }

        $engineName = $econfig['engine'];
        if (!class_exists($engineName))
        {
            throw new ViewException(sprintf('class "%s" is not exists', $engineName));
        }

        $engineInstance = new $engineName();
        $engineInstance->setEngineConfig($this, $econfig['config']);
        if (!$engineInstance instanceof IEngine)
        {
            throw new ViewException(sprintf('class "%s" is not instanceof \Tiny\MVC\View\Engine\IEngine', $engineName));
        }
        $engineInstance->setTemplateDir($this->_templateDir);
        $engineInstance->setCompileDir($this->_compileDir);
        if ($this->_cacheEnabled)
        {
            $engineInstance->setCache($this->cacheDir, $this->cacheLifetime);
        }
        $engineInstance->assign($this->_variables);
        $this->_engines[$engineName]['instance'] = $engineInstance;
        return $engineInstance;
    }
}
?>