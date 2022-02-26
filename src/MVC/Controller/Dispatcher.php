<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name Dispatcher.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:21:24
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:21:24 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Controller;

use Tiny\DI\ContainerInterface;
use Tiny\MVC\Application\ApplicationBase;

/**
 * 派发器
 *
 * @package Tiny.MVC.Controller
 * @since 2022年2月11日下午11:55:42
 * @final 2022年2月11日下午11:55:42
 */
class Dispatcher
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
    protected $controllerNamespace;
    
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
        $this->controllerNamespace = $namespace;
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
     * 分发
     *
     * @access protected
     * @param string $cname 控制器名称
     * @param string $aname 动作名称
     * @return mixed
     */
    public function dispatch(string $cname, string $aname, array $args = [], bool $isMethod = false)
    {
        $controllerClass = $this->getControllerClass($cname);
        $controllerInstance = $this->container->get($controllerClass);
        
        if (!$controllerInstance instanceof ControllerBase) {
            throw new DispatcherException(sprintf("Class %s does not implement the interface is named %s!", $controllerClass, ControllerBase::class), E_NOFOUND);
        }
        
        // application
        $this->container->call([$controllerInstance, 'setApplication']);
        
        // 执行前置函数 结果为false时不执行动作函数
        $beginRet = $this->container->call([$controllerInstance, 'onBeginExecute']);
        if (false === $beginRet)
        {
            return false;
        }
        
        //执行动作函数
        $actionMethod = $isMethod ? $aname : $this->getActionName($aname);
        if (!method_exists($controllerInstance, $actionMethod)) {
            if ($isMethod) {
                return;
            }
            throw new DispatcherException(sprintf("The controller named %s does not have an action named %s", $controllerClass, $actionMethod));
        }
        
        $result = $this->container->call([$controllerInstance, $actionMethod]);
        
        // 执行后触发动作
        $this->container->call([$controllerInstance, 'onEndExecute']);
        return $result;
    }
    
    /**
     * 获取控制器的类名
     * @param string $cname
     * @throws \Exception
     * @return string
     */
    public function getControllerClass(string $cname)
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
            
            $controllerClass = $this->controllerNamespace . $cparam;
            if (!class_exists($controllerClass)) {
                throw new DispatcherException("Faild to dispatch: {$controllerClass} does not exists!", E_NOFOUND);
            }
            $this->controllerClasses[$cname] = $controllerClass;
            return $controllerClass;
    }
    
    
    /**
     * 获取动作名称
     *
     * @param string $aname
     */
    public function getActionName($aname, bool $isEvent = false)
    {
        return $aname . $this->actionSuffix;
    }
}
?>