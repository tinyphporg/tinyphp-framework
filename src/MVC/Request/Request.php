<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月8日下午4:34:19
 * @Class List
 * @Function List
 * @History King 2017年3月8日下午4:34:19 0 第一次建立该文件
 *          King 2017年3月8日下午4:34:19 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Request;

use Tiny\MVC\ApplicationBase;

/**
 * 请求体基类
 *
 * @package Tiny.MVC.Request
 * @since 2017年4月4日下午8:47:29
 * @final 2017年4月4日下午8:47:29
 */
abstract class Request
{

    /**
     * 实例
     *
     * @var self
     */
    protected static $_instance;

    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $_app;

    /**
     * 控制器名称
     *
     * @var string
     */
    protected $_cname = 'Main';

    /**
     * 动作名
     *
     * @var string
     */
    protected $_aname = 'index';

    /**
     * 控制器参数名称
     *
     * @var string
     */
    protected $_cpname = 'c';

    /**
     * 动作参数名
     *
     * @var string
     */
    protected $_apname = 'a';

    /**
     * 供路由的参数
     *
     * @var string
     */
    protected $_routeParamString;

    /**
     * 路由参数
     *
     * @var array
     */
    protected $_routeParams = [];

    /**
     * 获取单例
     *
     * @return Base
     */
    public static function getInstance()
    {
        if (!self::$_instance)
        {
            $className = static::class;
            self::$_instance = new $className();
        }
        return self::$_instance;
    }

    /**
     * 设置当前应用实例
     *
     * @param ApplicationBase $app
     * @return void
     */
    public function setApplication(ApplicationBase $app)
    {
        $this->_app = $app;
    }

    /**
     * 获取控制器名称
     *
     * @return $controller
     */
    public function getController()
    {
        $cname = $this->param[$this->_cpname];
        if ($cname)
        {
            $this->_cname = $cname;
        }
        return $this->_cname;
    }

    /**
     * 获取动作名称
     *
     * @return string 动作名称
     */
    public function getAction()
    {
        $aname = $this->param[$this->_apname];
        if ($aname)
        {
            $this->_aname = $aname;
        }
        return strtolower($this->_aname);
    }

    /**
     * 设置控制器名称
     *
     * @param string $cname
     *        控制器名称
     * @return void
     */
    public function setController($cname)
    {
        if ($cname)
        {
            $this->_cname = $cname;
        }
    }

    /**
     * 设置动作名称
     *
     * @param string $aname
     *        动作名称
     * @return void
     */
    public function setAction($aname)
    {
        if ($aname)
        {
            $this->_aname = $aname;
        }
    }

    /**
     * 设置控制器输入的参数名称
     *
     * @param string $pname
     *        控制器参数名
     *        名称
     * @return void
     */
    public function setControllerParam($pname)
    {
        if ($pname)
        {
            $this->_cpname = $pname;
        }
    }

    /**
     * 设置动作输入的参数名称
     *
     * @param string $pname
     *        动作参数名
     *        动作名称
     * @return void
     */
    public function setActionParam($pname)
    {
        if ($pname)
        {
            $this->_apname = $pname;
        }
    }

    /**
     * 获取控制器输入的参数名
     *
     * @return string 控制器参数名称
     */
    public function getControllerParam()
    {
        return $this->_cpname;
    }

    /**
     * 获取动作输入的参数名称
     *
     * @return string 动作参数名
     */
    public function getActionParam()
    {
        return $this->_apname;
    }

    /**
     * 魔术函数获取
     *
     * @param string $key
     *        魔法函数获取参数值
     * @return mixed
     */
    public function __get($key)
    {
        $value = $this->_magicGet($key);
        if ($value)
        {
            $this->$key = $value;
        }
        return $value;
    }

    /**
     * 获取路由字符串
     *
     * @return string
     */
    abstract public function getRouterString();

    /**
     * 设置路由解析的参数
     *
     * @param array $param
     *        参数
     * @return void
     */
    abstract public function setRouterParam(array $param);

    /**
     * 魔法函数
     * 
     * @param string $key
     */
    protected function _magicGet($key)
    {

    }
}
?>