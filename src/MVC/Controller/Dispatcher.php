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
use Tiny\MVC\Module\ModuleManager;
use Tiny\MVC\View\ViewException;

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
     * 所有加载的模块配置
     * 
     * @var ModuleManager
     */
    protected $moduleManager;
    
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
     * @param ContainerInterface $container 当前容器实例
     * @param string $controllerNamespace 当前应用的默认控制器命名空间
     * @param string $actionSuffix 当前应用的默认动作后缀
     */
    public function __construct(ContainerInterface $container, string $controllerNamespace = '', string $actionSuffix = '')
    {
        $this->container = $container;
        if ($controllerNamespace) {
            $this->controllerNamespace = $controllerNamespace;
        }
        if ($actionSuffix) {
            $this->actionSuffix = $actionSuffix;
        }
    }
    
    /**
     * 派发前前检测
     * 
     * @param string $cname
     * @param string $aname
     * @param string $mname
     * @param array $args
     * @param bool $isMethod
     * @throws DispatcherException
     */
    public function preDispatch(string $cname, string $aname, string $mname = null, bool $isMethod = false)
    {
        $controllerClass = $this->getControllerClass($cname, $mname);
        $controllerInstance = $this->container->get($controllerClass);
        if (!$controllerInstance instanceof ControllerBase) {
            throw new DispatcherException(sprintf("Class %s does not implement the interface is named %s!", $controllerClass, ControllerBase::class), E_NOFOUND);
        }
        
        if (!method_exists($controllerInstance, 'onBeginExecute')) {
            throw new DispatcherException(sprintf("The controller named %s does not have an action named %s", $controllerClass, $actionMethod));
        }
        
        if (!method_exists($controllerInstance, 'onEndExecute')) {
            throw new DispatcherException(sprintf("The controller named %s does not have an action named %s", $controllerClass, $actionMethod));
        }
        
        // 执行动作函数
        $actionMethod = $isMethod ? $aname : $this->getActionName($aname);
        if (!method_exists($controllerInstance, $actionMethod)) {
            throw new DispatcherException(sprintf("The controller named %s does not have an action named %s", $controllerClass, $actionMethod));
        }
        return true;
    }
    
    /**
     * 执行派发
     *
     * @param string $cname 控制器名称
     * @param string $aname 动作或者成员函数名
     * @param array $args 调用动作或成员函数的参数数组
     * @param bool $isMethod 是否为调用成员函数，true为成员函数,false为动作函数
     * @throws DispatcherException
     * @return void|boolean|mixed
     */
    public function dispatch(string $cname, string $aname, string $mname = null, array $args = [], bool $isMethod = false)
    {   
        try {
            $controllerClass = $this->getControllerClass($cname, $mname);
        } catch(DispatcherException $e) {
            throw $e;
        }
        
        $controllerInstance = $this->container->get($controllerClass);
        if (!$controllerInstance instanceof ControllerBase) {
            throw new DispatcherException(sprintf("Class %s does not implement the interface is named %s!", $controllerClass, ControllerBase::class), E_NOFOUND);
        }
        
        // 执行前置函数 结果为false时不执行动作函数
        $beginRet = $this->container->call([
            $controllerInstance,
            'onBeginExecute'
        ]);
        
        if (false === $beginRet) {
            return false;
        }
        
        // 执行动作函数
        $actionMethod = $isMethod ? $aname : $this->getActionName($aname);
        if (!method_exists($controllerInstance, $actionMethod)) {
            if ($isMethod) {
                return;
            }
            throw new DispatcherException(sprintf("The controller named %s does not have an action named %s", $controllerClass, $actionMethod));
        }
        
        $result = $this->container->call([
            $controllerInstance,
            $actionMethod
        ]);
        
        // 执行后触发动作
        $this->container->call([
            $controllerInstance,
            'onEndExecute'
        ]);
        return $result;
    }
    
    /**
     * 获取控制器的类名
     *
     * @param string $cname 控制器名
     * @throws \Exception
     * @return string
     */
    public function getControllerClass(string $cname, string $mname = null)
    {
        if (!$cname) {
            throw new DispatcherException('Faild to get controller classname: cname is null!');
        }
        if ($mname && $this->container->has(ModuleManager::class)) {
            $this->moduleManager = $this->container->get(ModuleManager::class);
        }
        if ($mname && (!$this->moduleManager || !$this->moduleManager->has($mname))) {
            throw new DispatcherException(sprintf('Faild to get controller classname: modulename:%s is not exists!', $mname));
        }
        
        $groupKey = $mname ?: '__APPLICATION__NAMESPACES';
        if (!key_exists($groupKey, $this->controllerClasses))  {
            $this->controllerClasses[$groupKey] = [];
        }
        
        $groupClasses = &$this->controllerClasses[$groupKey];
        if (key_exists($cname, $groupClasses)) {
            return $groupClasses[$cname];
        }
        
        $cparam = preg_replace_callback("/\b\w/", function ($param) {
            return strtoupper($param[0]);
        }, $cname);
        
        $cparam = "\\" . preg_replace("/\/+/", "\\", $cparam);
        $controllerNamespace = $this->controllerNamespace;
        
        if ($mname) {
            $controllerNamespace = rtrim($this->moduleManager->getControllerNamespace($mname), '\\');
        }
        $controllerClass = $controllerNamespace . $cparam;
        if (!class_exists($controllerClass)) {
            throw new DispatcherException(sprintf('Faild to get controller classname: classname %s is not exists!', $controllerClass));
        }
        $groupClasses[$cname] = $controllerClass;
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