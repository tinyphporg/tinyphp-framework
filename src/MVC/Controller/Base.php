<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月10日下午10:55:57
 * @Class List
 * @Function List
 * @History King 2017年3月10日下午10:55:57 0 第一次建立该文件
 *          King 2017年3月10日下午10:55:57 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\Controller;

use Tiny\MVC\ApplicationBase;
use Tiny\Tiny;

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
     * @return Tiny\MVC\Model\Base
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
            case 'view':
                return $this->application->getView();
            case 'config':
                return $this->application->getConfig();
            case 'cache':
                return $this->application->getCache();
            case 'lang':
                return $this->application->getLang();
            case ('Model' == substr($key, -5) && strlen($key) > 6):
                return $this->application->getModel(substr($key, 0, -5));
            default:
                return FALSE;
        }
    }
}
?>