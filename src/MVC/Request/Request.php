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

use Tiny\MVC\Application\ApplicationBase;
use Tiny\Runtime\Param\Readonly;
use Tiny\MVC\Application\Properties;

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
     * 服务器参数数组
     *
     * @var Readonly
     */
    public $server;
    
    /**
     * 通用参数数组
     *
     * @var Readonly
     */
    public $param;
    
    /**
     * 路由参数
     *
     * @var Readonly
     */
    public $routeParam;
    
    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $application;
    
    /**
     * 控制器名称
     *
     * @var string
     */
    protected $controllerName = 'main';
    
    /**
     * 动作名
     *
     * @var string
     */
    protected $actionName = 'index';
    
    /**
     * 控制器参数名称
     *
     * @var string
     */
    protected $controllerParamName = 'c';
    
    /**
     * 动作参数名
     *
     * @var string
     */
    protected $actionParamName = 'a';
    
    /**
     * 默认模块
     * 
     * @var string
     */
    protected $moduleName = '';
    
    /**
     * 默认模块的参数名
     * 
     * @var string
     */
    protected $moduleParamName = 'm';
    
    /**
     * 控制器的命名空间
     * 
     * @var string
     */
    protected $controllerNamespace;
    
    /**
     * 供路由的上下文
     *
     * @var string
     */
    protected $routeContext;
    
    /**
     * 引入当前应用实例
     *
     * @param ApplicationBase $app
     */
    public function __construct(ApplicationBase $app)
    {
        $this->application = $app;
        if (!key_exists('argv', $_SERVER)) {
            $_SERVER['argv'] = [];
        }
        $this->server = new Readonly($_SERVER);
        unset($_SERVER);
        $this->param = new Readonly();
        $this->routeParam = new Readonly();
        $this->initData();
    }
    
    /**
     * 设置控制器名称
     *
     * @param string $cname 控制器名称
     */
    public function setControllerName(string $cname)
    {
        if ($cname) {
            $this->controllerName = $cname;
        }
    }
    
    /**
     * 获取控制器名称
     *
     * @return string
     */
    public function getControllerName(): string
    {
        return $this->controllerName;
    }
    
    /**
     * 设置动作名称
     *
     * @param string $aname 动作名称
     */
    public function setActionName(string $aname)
    {
        if ($aname) {
            $this->actionName = $aname;
        }
    }
    
    /**
     * 获取动作名称
     *
     * @return string
     */
    public function getActionName(): string
    {
        return $this->actionName;
    }
    
    /**
     * 设置控制器输入的参数名称
     *
     * @param string $pname 控制器参数名
     */
    public function setControllerParamName(string $pname)
    {
        if (!$pname) {
            return;
        }
        
        $this->controllerParamName = $pname;
        if ($this->param[$pname]) {
            $this->setControllerName($this->param[$pname]);
        }
    }
    
    /**
     * 获取控制器输入的参数名
     *
     * @return string 控制器参数名称
     */
    public function getControllerParamName(): string
    {
        return $this->controllerParamName;
    }
    
    /**
     * 设置动作名称
     *
     * @param string $mname 动作名称
     */
    public function setModuleName(string $mname)
    {
        if (!$mname) {
            return;
        }
        $this->moduleName = $mname;
    }
    
    /**
     * 获取动作名称
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }
    
    /**
     * 设置控制器输入的参数名称
     *
     * @param string $pname 控制器参数名
     */
    public function setModuleParamName(string $pname)
    {
        if (!$pname) {
            return;
        }
        
        $this->moduleParamName = $pname;
        if ($this->param[$pname]) {
            $this->setModuleName($this->param[$pname]);
        }
    }
    
    /**
     * 获取控制器的命名空间
     * 
     * @return string
     */
    public function getControllerNamespace()
    {
        return $this->controllerNamespace;
    }
    
    /**
     * 设置控制器的命名空间
     * 
     * @param string $namespace
     */
    public function setControllerNamespace(string $namespace)
    {
        if (!$namespace) {
            return;
        }
        $this->controllerNamespace = $namespace;
    }
    
    /**
     * 获取控制器输入的参数名
     *
     * @return string 控制器参数名称
     */
    public function getModuleParamName(): string
    {
        return $this->moduleParamName;
    }
    
    /**
     * 设置动作输入的参数名称
     *
     * @param string $pname 动作参数名
     */
    public function setActionParamName(string $pname)
    {
        if (!$pname) {
            return;
        }
        
        $this->actionParamName = $pname;
        if ($this->param[$pname]) {
            $this->setActionName($this->param[$pname]);
        }
    }
    
    /**
     * 获取动作输入的参数名称
     *
     * @return string 动作参数名
     */
    public function getActionParamName(): string
    {
        return $this->actionParamName;
    }
    
    /**
     * 设置路由参数
     *
     * @param array $params
     */
    public function setRouteParam(array $params)
    {
        $this->routeParam->merge($params);
        $this->param->merge($params);
    }
    
    /**
     * 设置路由上下文字符串
     *
     * @param string $routeContext
     */
    public function setRouteContext(string $routeContext)
    {
        $this->routeContext = $routeContext;
    }
    
    /**
     * 获取路由上下文字符串
     *
     * @return string
     */
    public function getRouteContext()
    {
        return $this->routeContext;
    }
    
    /**
     * 初始化数据数组
     */
    abstract protected function initData();
}
?>