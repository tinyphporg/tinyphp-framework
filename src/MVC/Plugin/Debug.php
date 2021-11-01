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

use Tiny\Runtime\ExceptionHandler;
use Tiny\Data\Db\Db;
use const Tiny\MVC\TINY_MVC_RESOURCES;
use Tiny\MVC\View\View;

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
     * 支持的debug动作列表
     *
     * @var array
     */
    const DEBUG_ACTION_LIST = [
        'outdebug',
        'showdocs'];

    /**
     * 当前应用实例
     *
     * @var \Tiny\MVC\ApplicationBase
     */
    protected $_app;

    /**
     * 当前app实例的request实例
     *
     * @var \Tiny\MVC\Request\Base
     */
    protected $_request;

    /**
     * 当前app的视图实例
     *
     * @var \Tiny\MVC\View\View
     */
    protected $_view;

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
     * @param $app \Tiny\MVC\ApplicationBase 当前应用实例
     */
    public function __construct(\Tiny\MVC\ApplicationBase $app)
    {
        $this->_app = $app;
        $this->_startTime = microtime(TRUE);
    }

    /**
     * 输出框架的文档和手册
     *
     * @return void
     */
    public function showDocsAction()
    {
        $content = $this->_view->fetch($this->_viewFolder . 'docs_header.php', [], TRUE);
        $content .= $this->_getDocContent();
        $content .= $this->_view->fetch($this->_viewFolder . 'docs_footer.php', [], TRUE);
        $this->_app->response->appendBody($content);
    }

    /**
     * 解析具体文档
     *
     * @return string
     */
    protected function _getDocContent()
    {
        $docpath = $this->_request->get->formatString('docpath', 'README.md');
        $docpath = $this->_viewDocDir . $docpath;
        if (! is_file($docpath))
        {
            return '';
        }
        $content = $this->_view->fetch($docpath, [], TRUE);
        $content = preg_replace_callback("/href=\"(?:https\:\/\/github.com\/saasjit\/tinyphp\/blob\/master\/docs\/(.+?)\.md)\"/i", [
            $this,
            '_parseGithubHref'], $content);
        $this->_app->response->appendBody($content);
    }

    /**
     * 替换掉github.com换成本地域名 加快加载速度
     *
     * @param array $matchs 匹配项数组
     * @return string
     */
    protected function _parseGithubHref($matchs)
    {
        return 'href="index.php?c=debug&a=showdocs&docpath=' . rawurlencode($matchs[1] . '.md') . '"';
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
        $cname = $this->_app->request->getController();
        if ($cname != 'debug')
        {
            return;
        }

        $this->_initDebugView();

        try
        {
            $aname = $this->_app->request->getAction();
            if ($aname == 'showdocs')
            {
                $this->showDocsAction();
            }
        }
        finally 
        {
            $this->_app->response->end();
        }
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
        if (! $this->_app->isDebug)
        {
            return;
        }
        $this->_outDebug();
    }

    /**
     * 输出debug信息
     *
     * @param string $aName 动作名称
     * @return void
     */
    protected function _outDebug()
    {
        $view = $this->_initDebugView();
        $viewDebugPath = $this->_viewDir . 'debug.php';
        if (! is_file($viewDebugPath))
        {
            return;
        }

        $interval = microtime(TRUE) - $this->_startTime;
        $memory = number_format(memory_get_peak_usage(TRUE) / 1024 / 1024, 4);
        
        // 路由信息
        $router = $this->_app->getRouter()->getMatchedRouter();
        if ($router)
        {
            $routerName = get_class($router);
        }
        $routerUrl = $this->_app->request->getRouterString();
        $routerParams = $this->_app->getRouter()->getParams();
        
        
        // 文档手册信息
        $docsUrl = $this->_app->getRouter()->rewriteUrl([
            'c' => 'debug',
            'a' => 'showdocs']);
        
        // 控制器信息
        $controllerList = [];
        $controllers = $this->_app->getControllerList();
        foreach ($controllers as $cpath => $controller)
        {
            $controllerList[] = $cpath . '(' . get_class($controller) . ')';
        }
        $controllerList = join(' ', $controllerList);
        
        // 视图解析信息
        $viewPaths = $view->getTemplateList();
        $viewAssign = $view->getAssigns();

        // 模型层信息
        $models = [];
        $modelList = $this->_app->getModels();
        foreach ($modelList as $model)
        {
            $models[] = get_class($model);
        }
        $models = join(' ', $models);
        
        // DEBUG集合
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
            'debugDocsUrl' => $docsUrl,
            'debugExceptions' => ExceptionHandler::getInstance()->getExceptions()];
        
        // 附加debug信息到输出
        $body = $view->fetch($viewDebugPath, $debugs, TRUE);
        $this->_app->response->appendBody($body);
    }

    /**
     * 初始化debug
     *
     * @param void
     * @return View
     */
    protected function _initDebugView()
    {
        if (! $this->_view)
        {
            $this->_request = $this->_app->request;
            $this->_view = $this->_app->getView();
            $this->_viewDir = TINY_MVC_RESOURCES . 'view/debug/';
            $this->_viewDocDir = dirname(TINY_FRAMEWORK_PATH) . '/docs/';
        }
        return $this->_view;
    }
}
?>