<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Debug.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2017年3月12日下午2:05:36 0 第一次建立该文件
 *          King 2017年3月12日下午2:05:36 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\Plugin;

use Tiny\MVC\ApplicationBase;
use Tiny\Runtime\ExceptionHandler;
use Tiny\Data\Db\Db;
use const Tiny\MVC\TINY_MVC_RESOURCES;

/**
 * DEBUG插件
 *
 * @package Tiny.Application.Plugin
 * @since 2017年3月12日下午2:05:40
 * @final 2017年3月12日下午2:05:40
 */
class Debug implements Iplugin
{

    /**
     * 当前应用实例
     *
     * @var \Tiny\MVC\ApplicationBase
     */
    protected $_app;

    /**
     * 开始时间
     *
     * @var float
     */
    protected $_startTime = 0;

    /**
     * 执行间隔
     *
     * @var int
     *
     */
    protected $_interval = 0;

    /**
     * debug的视图文件夹
     *
     * @var string
     */
    protected $_viewFolder;

    /**
     * 初始化
     *
     * @param $app ApplicationBase
     *        当前应用实例
     * @return void
     */
    public function __construct(ApplicationBase $app)
    {
        $this->_app = $app;
        $this->_startTime = microtime(true);
        $this->_viewFolder = TINY_MVC_RESOURCES . 'view/debug/';
    }

    public function showDocs()
    {
        $content = $this->_app->getView()->fetch('main/README.md');
        $content = preg_replace_callback("/href=\"(https\:\/\/github.com\/saasjit\/tinyphp\/blob\/master\/docs\/(.+?)\.md)\"/i", [$this, '_parseGithubHref'], $content);
        return $content;
    }
        
    protected function _parseGithubHref($matchs)
    {
        print_r($matchs);
    }
    /**
     * Debug动作执行
     *
     * @param string $aName
     *        动作名称
     * @return void
     */
    public function onAction($aName)
    {
        $path = $this->_viewFolder . strtolower($aName) . '.php';
        if (!is_file($path))
        {
            return;
        }
        $interval = microtime(TRUE) - $this->_startTime;
        $memory = number_format(memory_get_peak_usage(TRUE) / 1024 / 1024, 4);
        $router = $this->_app->getRouter()->getMatchedRouter();
        if ($router)
        {
            $routerName = get_class($router);
        }
        $routerUrl = $this->_app->request->getRouterString();
        $routerParams = $this->_app->getRouter()->getParams();
        
        $controllers = $this->_app->getControllerList();
        
        $controllerList = [];
        foreach($controllers as $cpath => $controller)
        {
            $controllerList[] = $cpath . '('. get_class($controller) . ')';
        }
        $controllerList = join(' ', $controllerList);
        $view = $this->_app->getView();
        $viewPaths = $view->getTemplateList();
        $viewAssign = $view->getAssigns();
        
        $modelList  = $this->_app->getModels();
        $models = [];
        foreach ($modelList as $model)
        {
            $models[] = get_class($model);
        }
        $models = join(' ', $models);
        $debugs = [
            'debug' => $this,
            'debugInterval' => $interval,
            'debugMemory' => $memory,
            'debugViewPaths' => $viewPaths,
            'debugViewAssign' => $viewAssign,
            'debugDatamessage' => Db::getQuerys(),
            'debugRouterName' => $routerName,
            'debugRouterUrl' => $routerUrl,
            'debugRouterParams' => $routerParams,
            'debugControllerList' => $controllerList,
            'debugModelList' => $models,
            'app' => $this->_app,
            'debugDocs' => $this->showDocs(),
            'debugExceptions' => ExceptionHandler::getInstance()->getExceptions()
        ];
        $body = $view->fetch($path, $debugs, TRUE);
        $this->_app->response->appendBody($body);
    }

    /**
     * 本次请求初始化时发生的事件
     *
     * @return void
     */
    public function onBeginRequest()
    {
    }

    /**
     * 本次请求初始化结束时发生的事件
     *
     * @return void
     */
    public function onEndRequest()
    {
        
    }

    /**
     * 执行路由前发生的事件
     *
     * @return void
     */
    public function onRouterStartup()
    {
    }

    /**
     * 执行路由后发生的事件
     *
     * @return void
     */
    public function onRouterShutdown()
    {
        
    }

    /**
     * 执行分发前发生的动作
     *
     * @return void
     */
    public function onPreDispatch()
    {
        
    }

    /**
     * 执行分发后发生的动作
     *
     * @return void
     */
    public function onPostDispatch()
    {
        if (!$this->_app->isDebug)
        {
            return;
        }
        $this->onAction('debug');
    }
}
?>