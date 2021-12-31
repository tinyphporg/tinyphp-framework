<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Controller.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月10日下午11:09:50
 * @Class List
 * @Function List
 * @History King 2017年3月10日下午11:09:50 0 第一次建立该文件
 *          King 2017年3月10日下午11:09:50 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Controller;

use Tiny\MVC\ApplicationBase;
use Tiny\MVC\View\View;

class Dispatcher
{
    /**
     * 分发
     *
     * @access protected
     * @param string $cname 控制器名称
     * @param string $aname 动作名称
     * @return mixed
     */
    public function dispatch(string $cname = NULL, string $aname = NULL, array $args = [])
    {
        // 获取控制器实例
        $controller = $this->getController($cname);
        $this->controller = $controller;
        
        // 获取执行动作名称
        $action = $this->getAction($aname, $isEvent);
        
        // 触发事件
        if (method_exists($controller, $action))
        {
            return call_user_func_array([$controller, $action], $args);
        }
        
        // 执行前返回FALSE则不执行派发动作
        $ret = call_user_func_array([$controller, 'onBeginExecute'], $args);
        if (FALSE === $ret)
        {
            return FALSE;
        }
        
        if (!method_exists($controller, $action))
        {
            $cname = get_class($controller);
            $aname = $action;
            throw new ApplicationException("Dispatch error: The Action '{$aname}' of Controller '{$cname}' is not exists ", E_NOFOUND);
        }
        $ret = call_user_func_array([$controller, $action], $args);
        call_user_func_array([$controller, 'onEndExecute'], $args);
        return $ret;
    }
 
    /**
     * 简单获取控制器
     *
     * @param string $cName 模型名称
     * @return Base
     */
    public function getController($cname)
    {
        $cname = $cname ?: $this->request->getController();
        if ($this->_controllers[$cname])
        {
            return $this->_controllers[$cname];
        }
        $cparam = preg_replace_callback("/\b\w/", function ($param) {
            return strtoupper($param[0]);
        }, $cname);
            
            
            $cparam = "\\" . preg_replace("/\/+/", "\\", $cparam);
            $controllerName = $this->_cNamespace . $cparam;
            if (!class_exists($controllerName))
            {
                throw new ApplicationException("Dispatch errror:controller,{$controllerName}不存在，无法加载", E_NOFOUND);
            }
            
            $controllerInstance = new $controllerName();
            if (!$controllerInstance instanceof \Tiny\MVC\Controller\Base)
            {
                throw new ApplicationException("Controller:'{$controllerName}' is not instanceof Tiny\MVC\Controlller\Controller!", E_NOFOUND);
            }
            $controllerInstance->setApplication($this);
            $this->_controllers[$cname] = $controllerInstance;
            return $controllerInstance;
    }

    /**
     * 获取动作名称
     *
     * @param string $aname
     */
    public function getAction($aname, bool $isEvent = FALSE)
    {
        $aname = $aname ?: $this->request->getAction();
        $aname = $isEvent ? $aname : $aname . 'Action';
        return $aname;
    }
    
}

/**
 * 控制器积类
 *
 * @package Tiny.Application.Controller
 * @since 2017年3月12日下午2:57:20
 * @final 2017年3月12日下午2:57:20
 */
abstract class Base
{
    
    /**
     * 当前应用程序实例
     *
     * @var \Tiny\MVC\WebApplication
     */
    public $application;
    
    /**
     * 当前应用程序的状态和配置数据
     *
     * @var \Tiny\Config\Configuration
     */
    public $properties;
    
    /**
     * 容器实例
     * @var Container
     */
    public $container;
    
    /**
     * 当前WEB请求参数
     *
     * @var \Tiny\MVC\Request\WebRequest
     */
    public $request;
    
    /**
     * 当前WEB请求响应实例
     *
     * @var \Tiny\MVC\Response\WebResponse
     */
    public $response;
    
    /**
     * 设置当前应用实例
     *
     * @param
     *        void
     * @return void
     */
    public function setApplication(ApplicationBase $app)
    {
        $this->application = $app;
        $this->request = $app->request;
        $this->response = $app->response;
        $this->properties = $app->properties;
    }
    
    /**
     * 关闭或开启调试模块
     *
     * @param bool $isDebug
     *        是否输出调试模块
     * @return void
     */
    public function setDebug($isDebug)
    {
        $this->application->isDebug = (bool)$isDebug;
    }
    
    /**
     * 写入日志
     *
     * @param string $id
     *        日志ID
     * @param string $message
     *        日志信息
     * @param int $priority
     *        日志优先级别 0-7
     * @param array $extra
     *        附加信息
     * @return void
     */
    public function log($id, $message, $priority = 1, $extra = [])
    {
        return $this->application->getLogger()->log($id, $message, $priority, $extra);
    }
    
    /**
     * 执行动作前触发
     *
     * @return void
     */
    public function onBeginExecute()
    {
    }
    
    /**
     * 结束后触发该事件
     *
     * @return void
     */
    public function onEndExecute()
    {
    }
    
    /**
     * 初始化视图实例后执行该函数
     *
     * @return void
     */
    public function onViewInited()
    {
    }
    
    /**
     * 给试图设置预定义变量
     *
     * @param string|array $key
     *        变量键 $key为array时 $value默认为空
     * @param mixed $value
     *        变量值
     * @return bool
     */
    public function assign($key, $value = NULL)
    {
        return $this->view->assign($key, $value);
    }
    
    /**
     * 解析视图模板，注入到响应实例里
     *
     * @param string $viewPath
     *        视图模板文件的相对路径
     *        视图相对路径
     * @return void
     */
    public function parse($viewPath)
    {
        return $this->view->display($viewPath);
    }
    
    /**
     * 解析视图模板并注入response
     * @param string $viewPath
     * @return void
     */
    public function display($viewPath)
    {
        return $this->view->display($viewPath);
    }
    
    /**
     * 解析视图模板，并返回解析后的字符串
     *
     * @param string $viewPath
     *        视图模板文件的相对路径
     *
     * @return void
     */
    public function fetch($viewPath)
    {
        return $this->view->fetch($viewPath);
    }
    
    /**
     * 加载Model
     *
     * @param string $modelName
     *        模型名称
     * @return Base
     */
    public function getModel($modelName)
    {
        return $this->application->getModel($modelName);
    }
    
    /**
     * 调用另外一个控制器的动作并派发
     *
     * @param string $cName
     *        控制器名称
     * @param string $aName
     *        动作名称
     * @return void
     */
    public function toDispathcher($cName, $aName)
    {
        return $this->application->dispatch($cName, $aName);
    }
    
    /**
     * 输出格式化的JSON串
     *
     * @param array ...$params
     *        输入参数
     */
    public funCtion outFormatJSON(...$params)
    {
        return $this->response->outFormatJSON(...$params);
    }
    
    /**
     * 魔法函数，加载视图层
     *
     * @param $key string
     *        属性名
     * @return mixed view Tiny\MVC\Viewer\Viewer 视图层对象
     *         config Tiny\Config\Configuration 默认配置对象池
     *         cache Tiny\Cache\Cache 默认缓存对象池
     *         lang 语言对象
     *         *Model 尾缀为Model的模型对象
     */
    public function __get($key)
    {
        $ins = $this->_magicGet($key);
        if ($ins)
        {
            $this->{$key} = $ins;
        }
        if ('view' == $key)
        {
            $this->onViewInited();
        }
        return $ins;
    }
    
    /**
     * 魔术方式获取属性
     *
     * @param string $key
     * @return mixed
     */
    protected function _magicGet($key)
    {
        switch ($key)
        {
            case 'cache':
                return $this->application->container->get('cache');
            case 'view':
                //return $this->application->container->get('view');
                return $this->application->container->get(View::class);
            case 'config':
                return $this->application->container->get('config');
            case 'lang':
                return $this->application->container->get('lang');
            case ('Model' == substr($key, -5) && strlen($key) > 6):
                return $this->application->getModel(substr($key, 0, -5));
            default:
                return FALSE;
        }
    }
}

/**
 * WEB控制器
 *
 * @package Tiny.Application.Controller
 * @since 2017年3月11日上午12:20:13
 * @final 2017年3月11日上午12:20:13
 */
abstract class Controller extends Base
{

    /**
     * 魔术方式获取属性
     *
     * @param string $key
     * @return mixed session HttpsSession操作对象
     *         cookie HttpCookie操作对象
     */
    protected function _magicGet($key)
    {
        switch ($key)
        {
            case 'cookie':
                return $this->application->getCookie();
            case 'session':
                return $this->application->getSession();
            default:
                return parent::_magicGet($key);
        }
    }
}

/**
 * 命令行控制器基类
 *
 * @package Tiny.Application.Controller;
 * @since 2017年3月12日下午3:04:19
 * @final 2017年3月12日下午3:04:19
 */
abstract class ConsoleController extends Base
{
    
}
?>