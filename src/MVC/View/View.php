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
     * 引擎数组
     *
     * @var array
     */
    protected static $_enginePolicys = [
        'php' => '\Tiny\MVC\View\Engine\PHP',
        'tpl' => '\Tiny\MVC\View\Engine\Smarty',
        'htm' => '\Tiny\MVC\View\Engine\Template'
    ];

    /**
     * 视图层预设的值
     *
     * @var array
     */
    protected $_variables = [];

    /**
     * 加载的视图引擎实例
     *
     * @var array
     */
    protected $_engines = [];

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
        if (! self::$_instance)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 通过扩展名绑定视图处理引擎
     *
     * @param string $engineName
     *            引擎对象类
     * @param mixed $ext
     *            string 视图扩展名
     *            array 时可绑定多个扩展
     * @return bool
     */
    public static function bindEngineName($engineName, $ext)
    {
        if (is_array($ext))
        {
            foreach ($ext as $e)
            {
                self::bindEngine($engineName, $e);
            }
            return;
        }
        $ext = strtolower($ext);
        self::$_enginePolicys[$ext] = $engineName;
    }

    /**
     * 根据扩展名获取视图处理引擎的类名
     *
     * @param string $ext
     * @return string
     */
    public static function getEngineNameByExt($ext)
    {
        $ext = strtolower($ext);
        if (key_exists($ext, self::$_enginePolicys))
        {
            return self::$_enginePolicys[$ext];
        }
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
     * 解析视图获取字符串
     *
     * @param string $filepath
     *            string 视图相对路径
     * @param bool $isAbsolute
     *            是否绝对位置
     * @return string
     */
    public function fetch($filepath, $isAbsolute = FALSE)
    {
        $this->_templateFiles[] = $filepath;
        return $this->getEngineByPath($filepath)->fetch($filepath, $isAbsolute);
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
        $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));
        $ename = self::getEngineNameByExt($ext);
        if (!$ename)
        {
            throw new ViewException('Viewer error: ext"' . $ext . '"is not bind');
        }
        return $this->_getEngineInstance($ename);
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
     * 弹出消息框并中断访问
     *
     * @param string $message
     *            消息内容
     * @param string $url
     *            跳转地址
     * @param string $subject
     *            消息标题
     * @param string $timeout
     *            跳转延时/秒
     * @return void exied
     */
    public function message($message, $url = '', $subject = '', $timeout = '')
    {
        return Helper\MessageBox::show($message, $url, $subject, $timeout);
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
     * 初始化视图层
     *
     * @return void
     */
    protected function __construct()
    {
        $this->_variables['view'] = $this;
    }

    /**
     * 根据类名获取Viewer实例
     *
     * @param IEngine $className
     *            视图解析类名称
     * @return IEngine
     */
    protected function _getEngineInstance($ename)
    {
        
        if (key_exists($ename, $this->_engines))
        {
            return $this->_engines[$ename];
        }
       
        if (!in_array($ename, self::$_enginePolicys))
        {
            throw new ViewException(sprintf('模板引擎对象"%s"未注册', $ename));
        }
        $engineInstance = new $ename();
        $engineInstance->setTemplateDir($this->_templateDir);
        $engineInstance->setCompileDir($this->_compileDir);   
        
        if ($this->_cacheEnabled)
        {
            $engineInstance->setCache($this->cacheDir, $this->cacheLifetime);
        }
        $engineInstance->assign($this->_variables);
        $this->_engines[$ename] = $engineInstance;
        return $engineInstance;
    }
}
?>