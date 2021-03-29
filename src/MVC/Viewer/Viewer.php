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
namespace Tiny\MVC\Viewer;

define('IN_ZEROAI_VIEW_TEMPLATE', TRUE);


/**
 * 视图层
 *
 * @package Tiny.Application.Viewer
 * @since : Mon Dec 12 01:15 51 CST 2011
 * @final : Mon Dec 12 01:15 51 CST 2011
 */
class Viewer implements \ArrayAccess
{

    /**
     * View当前实例
     *
     * @var Viewer
     */
    protected static $_instance;

    /**
     * 引擎数组
     *
     * @var array
     */
    protected $_viewEngines = array(
        'php' => '\Tiny\MVC\Viewer\PHP',
        'tpl' => '\Tiny\MVC\Viewer\Smarty',
        'htm' => '\Tiny\MVC\Viewer\Template'
    );

    /**
     * 视图层预设的值
     *
     * @var array
     */
    protected $_variables = [];

    /**
     * 加载的视图实例
     *
     * @var array
     */
    protected $_viewers = [];

    /**
     * 各种视图引擎配置
     *
     * @var array
     */
    protected $_viewConfig = [];

    /**
     * 视图根路径
     *
     * @var string
     */
    protected $_basePath = '';

    /**
     * 模板路径
     *
     * @var string
     */
    protected $_templatePath = '';

    /**
     * 模板编译路径
     *
     * @var string
     */
    protected $_compilePath = '';

    /**
     * 解析的视图变量
     *
     * @var array
     */
    protected $_parsePaths = [];

    /**
     * 获取当前视图单一实例
     *
     * @return Viewer
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
     * @param string $ext 视图扩展名
     * @param string $engineName 引擎对象名
     * @return bool
     */
    public function bindEngineByExt($ext, $engineName)
    {
        $this->_viewEngines[strtolower($ext)] = $engineName;
    }

    /**
     * 获取模板文件所在目录
     *
     * @return string
     */
    public function getTemplatePath()
    {
        return $this->_templatePath;
    }

    /**
     * 设置模板文件所在目录
     *
     * @param string $path 模板文件所在目录路径
     * @return Viewer
     */
    public function setTemplatePath($path)
    {
        $this->_templatePath = $path;
        return $this;
    }

    /**
     * 设置模板编译存放的目录
     *
     * @param string $path 编译后的文件存放目录路径
     * @return Viewer
     */
    public function setCompilePath($path)
    {
        $this->_compilePath = $path;
        return $this;
    }

    /**
     * 获取模板文件编译后所在目录
     *
     * @return string
     */
    public function getCompilePath()
    {
        return $this->_compilePath;
    }


    /**
     * 获取视图解析的模板路径和引擎
     *
     * @return array
     */
    public function getParsePaths()
    {
        return $this->_parsePaths;
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
     * @param string|array $key 当key为数组时，可添加多个预编译变量
     * @return Viewer
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
     * 设置视图根路径
     *
     * @param string $path 相对目录名称
     * @return void
     */
    public function setBasePath($path)
    {
        $this->_basePath .= $path . DIRECTORY_SEPARATOR;
    }

    /**
     * 解析视图获取字符串
     *
     * @param string $filepath string 视图相对路径
     * @param bool $isAbsolute 是否绝对位置
     * @return string
     */
    public function fetch($filepath, $isAbsolute = FALSE)
    {
        return $this->_getViewerByPath($filepath)->fetch($filepath, $isAbsolute);
    }

    /**
     * 实现数组接口之获取元素
     *
     * @param string $key 键
     * @return NULL|string
     */
    public function offsetGet($key)
    {
        return $this->_variables[$key];
    }

    /**
     * 实现数组接口之设置元素
     *
     * @param string $key 键
     * @param mixed $value 值
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->_variables[$key] = $value;
    }

    /**
     * 实现ArrayAccess接口之是否存在元素
     *
     * @param string $key 键
     * @return bool
     */
    public function offsetExists($key)
    {
        return (bool)$this->_variables[$key];
    }

    /**
     * 实现ArrayAccess接口之删除元素
     *
     * @param string $key 键
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->_variables[$key]);
    }

    /**
     * 生成url
     *
     * @param array $params 网址参数
     * @param string $mod 生成的url类型
     * @param string $suffix 当$mod = r时的网址后缀
     * @return string
     */
    public function url($params, $mod = 'r', $suffix = '.html')
    {
        return Helper\Url::get($params, $mod, $suffix);
    }

    /**
     * 弹出消息框并中断访问
     *
     * @param string $message 消息内容
     * @param string $url 跳转地址
     * @param string $subject 消息标题
     * @param string $timeout 跳转延时/秒
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
        $sp = new Helper\SplitPage(['url' => $url,'total' => $total,'size' => $size,'pageid' => $pageId,'color' => $color,'css' => $isOut
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
    }

    /**
     * 根据文件路径获取绑定的视图模板引擎
     *
     * @param string $filepath 视图文件路径
     * @return IViewer
     */
    protected function _getViewerByPath($filepath)
    {
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $ename = $this->_viewEngines[$ext];
        if (! $ename)
        {
            throw new ViewerException('Viewer error: ext"' . $ext . '"is not bind');
        }

        $parsePath = $this->_templatePath . $this->_basePath . DIRECTORY_SEPARATOR . $filepath;
        $this->_parsePaths[$parsePath] = $ename;
        return $this->_getViewer($ename);
    }


    /**
     * 根据类名获取View实例
     *
     * @param IViewer $className 视图解析类名称
     * @return IViewer
     */
    protected function _getViewer($ename)
    {
        static $viewers = [];
        if ($viewers[$ename])
        {
            return $viewers[$ename];
        }
        if (! in_array($ename, $this->_viewEngines))
        {
            throw new ViewerException(sprintf('模板引擎对象"%s"未注册', $ename));
        }

        $viewer = new $ename();
        $viewers[$ename] = $viewer;

        $basePath = $this->_basePath;
        $viewer->setTemplateFolder($this->_templatePath . $basePath);
        $viewer->setCompileFolder($this->_compilePath);
        $viewer->assign($this->_variables);
        return $viewer;
    }
}
?>