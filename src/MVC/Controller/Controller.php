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
use Tiny\DI\Definition\DefinitionProviderInterface;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\ContainerInterface;
use Tiny\MVC\Request\Request;
use Tiny\MVC\Application\Properties;
use Tiny\MVC\Response\Response;
use Tiny\MVC\WebApplication;
use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Response\WebResponse;
use Tiny\MVC\ConsoleApplication;
use Tiny\MVC\Request\ConsoleRequest;
use Tiny\MVC\Response\ConsoleResponse;

class Dispatcher implements DefinitionProviderInterface
{
    
    /**
     * 容器实例
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 控制器的命名空间
     *
     * @var string
     */
    protected $namespace;
    
    /**
     * 动作名称前缀
     *
     * @var string
     */
    protected $actionSuffix = 'Action';
    
    /**
     * 控制器定义集合
     *
     * @var array
     */
    protected $controllerDefinitions = [];
    
    /**
     * 控制器的名称集合
     *
     * @var array
     */
    protected $controllerClasses = [];
    
    /**
     * 设置控制器的命名空间
     *
     * @param string $namespace
     */
    public function setControllerNamespace(string $namespace)
    {
        $this->namespace = $namespace;
    }
    
    /**
     * 设置动作后缀
     *
     * @param string $suffix
     */
    public function setActionSuffix(string $suffix)
    {
        $this->actionSuffix = $suffix;
    }
    
    /**
     *
     * @param ApplicationBase $app
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     *
     * @param string $name
     * @return \Tiny\DI\Definition\ObjectDefinition
     */
    public function getDefinition(string $name)
    {
        if (strpos($name, $this->namespace) !== 0) {
            return false;
        }
        if (!key_exists($name, $this->controllerDefinitions)) {
            $this->controllerDefinitions[$name] = new ObjectDefinition($name, $name);
        }
        return $this->controllerDefinitions[$name];
    }
    
    /**
     *
     * @return array
     */
    public function getDefinitions(): array
    {
        return [];
    }
    
    /**
     * 分发
     *
     * @access protected
     * @param string $cname 控制器名称
     * @param string $aname 动作名称
     * @return mixed
     */
    public function dispatch(string $cname, string $aname)
    {
        $controllerClass = $this->getControllerClass($cname);
        $controllerInstance = $this->container->get($controllerClass);
        if (!$controllerInstance instanceof ControllerBase) {
            throw new \Exception("Controller:'{$controllerClass}' is not instanceof Tiny\MVC\Controlller\Controller!", E_NOFOUND);
        }
        
        $actionMethod = $this->getActionName($aname);
        
        // 执行前置函数 结果为false时不执行动作函数
        $beginRet = $this->container->call([$controllerInstance, 'onBeginExecute']);
        if (false === $beginRet)
        {
            return false;
        }
        
        //执行动作函数
        if (!method_exists($controllerInstance, $actionMethod)) {
            throw new \Exception(sprintf("Dispatch error: The Action '{$aname}' of Controller '{$cname}' is not exists ", $actionMethod, $controllerClass));
        }
        $this->container->call([$controllerInstance, $actionMethod]);
        
        
        // 执行后触发动作
        $this->container->call([$controllerInstance, 'onEndExecute']);
    }
    
    protected function getControllerClass($cname)
    {
        if (!$cname) {
            throw new \Exception('aaa');
        }
        if (key_exists($cname, $this->controllerClasses)) {
            return $this->controllerClasses[$cname];
        }
        
        $cparam = preg_replace_callback("/\b\w/", function ($param) {
            return strtoupper($param[0]);
        }, $cname);
        $cparam = "\\" . preg_replace("/\/+/", "\\", $cparam);
        
        $controllerClass = $this->namespace . $cparam;
        if (!class_exists($controllerClass)) {
            throw new \Exception("Dispatch errror:controller,{$controllerClass}不存在，无法加载", E_NOFOUND);
        }
        $this->controllerClasses[$cname] = $controllerClass;
        return $controllerClass;
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
        if ($this->_controllers[$cname]) {
            return $this->_controllers[$cname];
        }
        $cparam = preg_replace_callback("/\b\w/", function ($param) {
            return strtoupper($param[0]);
        }, $cname);
        
        if (!class_exists($controllerName)) {
            throw new \Exception("Dispatch errror:controller,{$controllerName}不存在，无法加载", E_NOFOUND);
        }
        
        $controllerInstance = new $controllerName();
        if (!$controllerInstance instanceof \Tiny\MVC\Controller\Base) {
            throw new \Exception("Controller:'{$controllerName}' is not instanceof Tiny\MVC\Controlller\Controller!", E_NOFOUND);
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
    public function getActionName($aname, bool $isEvent = FALSE)
    {
        return $aname . $this->actionSuffix;
    }
}

/**
 * 控制器积类
 *
 * @package Tiny.Application.Controller
 * @since 2017年3月12日下午2:57:20
 * @final 2017年3月12日下午2:57:20
 */
abstract class ControllerBase
{
    
    /**
     * 当前应用程序实例
     *
     * @var ApplicationBase
     */
    protected $application;
    
    /**
     * 当前应用程序的状态和配置数据
     *
     * @var Properties
     */
    protected Properties $properties;
    
    /**
     * 当前请求参数
     *
     * @var Request
     */
    protected $request;
    
    /**
     * 当前响应实例
     *
     * @var Response
     */
    protected $response;
        
    /**
     *
     * @autowired
     * @param ApplicationBase $app
     */
    public function init(ApplicationBase $app)
    {
        $this->properties = $app->properties;
        $this->application = $app;
        $this->request = $app->request;
        $this->response = $app->response;
    }
    
    /**
     * 关闭或开启调试模块
     *
     * @param bool $isDebug 是否输出调试模块
     * @return void
     */
    public function setDebug($isDebug)
    {
        $this->application->isDebug = (bool)$isDebug;
    }
    
    /**
     * 写入日志
     *
     * @param string $id 日志ID
     * @param string $message 日志信息
     * @param int $priority 日志优先级别 0-7
     * @param array $extra 附加信息
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
     * 给试图设置预定义变量
     *
     * @param string|array $key 变量键 $key为array时 $value默认为空
     * @param mixed $value 变量值
     * @return bool
     */
    public function assign($key, $value = NULL)
    {
        return $this->getView()->assign($key, $value);
    }
    
    /**
     * 解析视图模板，注入到响应实例里
     *
     * @param string $viewPath 视图模板文件的相对路径
     *        视图相对路径
     * @return void
     */
    public function parse($viewPath)
    {
        return $this->getView()->display($viewPath);
    }
    
    /**
     * 解析视图模板并注入response
     *
     * @param string $viewPath
     * @return void
     */
    public function display($viewPath)
    {
        return $this->getView()->display($viewPath);
    }
    
    /**
     * 解析视图模板，并返回解析后的字符串
     *
     * @param string $viewPath 视图模板文件的相对路径
     * @return void
     */
    public function fetch($viewPath)
    {
        return $this->getView()->fetch($viewPath);
    }
    
    /**
     * 加载Model
     *
     * @param string $modelName 模型名称
     * @return Base
     */
    public function getModel($modelName)
    {
        return $this->application->getModel($modelName);
    }
    
    /**
     * 调用另外一个控制器的动作并派发
     *
     * @param string $cName 控制器名称
     * @param string $aName 动作名称
     * @return void
     */
    public function toDispathcher($cName, $aName)
    {
        return $this->application->dispatch($cName, $aName);
    }
    
    /**
     * 输出格式化的JSON串
     *
     * @param array ...$params 输入参数
     */
    public function outFormatJSON(...$params)
    {
        return $this->response->outFormatJSON(...$params);
    }
    
    /**
     * 获取视图实例
     * 
     * @return View
     */
    protected function getView()
    {
        if (!$this->view)
        {
            $this->view = $this->application->get(View::class);
        }
        return $this->view;
    }
}

/**
 * WEB控制器
 *
 * @package Tiny.Application.Controller
 * @since 2017年3月11日上午12:20:13
 * @final 2017年3月11日上午12:20:13
 */
abstract class Controller extends ControllerBase
{
    
    /**
     * 当前应用程序实例
     *
     * @var WebApplication
     */
    protected $application;
    
    /**
     * 当前请求参数
     *
     * @var WebRequest
     */
    protected $request;
    
    /**
     * 当前响应实例
     *
     * @var WebResponse
     */
    protected $response;
}

/**
 * 命令行控制器基类
 *
 * @package Tiny.Application.Controller;
 * @since 2017年3月12日下午3:04:19
 * @final 2017年3月12日下午3:04:19
 */
abstract class ConsoleController extends ControllerBase
{
    /**
     * 当前应用程序实例
     *
     * @var ConsoleApplication
     */
    protected $application;
    
    /**
     * 当前请求参数
     *
     * @var ConsoleRequest
     */
    protected $request;
    
    /**
     * 当前响应实例
     *
     * @var ConsoleResponse
     */
    protected $response;
}
?>