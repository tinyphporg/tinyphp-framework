<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name DebugEventListener.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月1日下午1:55:05
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月1日下午1:55:05 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event\Plugin;

use Tiny\MVC\Event\MvcEvent;
use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\View\View;
use Tiny\Runtime\Runtime;
use Tiny\Data\Db\Db;
use Tiny\MVC\Router\Router;
use Tiny\MVC\Application\ConsoleApplication;
use Tiny\MVC\Controller\Dispatcher;
use Tiny\Runtime\ExceptionHandler;
use Tiny\MVC\Event\Listener\DispatchEventListener;
use Tiny\MVC\Event\Listener\RouteEventListener;
use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;

class Debug implements RouteEventListener, DispatchEventListener
{
    
    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * 当前应用的请求实例
     * 
     * @var Request
     */
    protected $request;
    
    /**
     * 当前应用的响应实例
     * 
     * @var Response
     */
    protected $response;
    
    /**
     * 当前应用的视图实例
     * @var View
     */
    protected $view;
    
    /**
     * 当前的运行时实例
     * 
     * @var Runtime
     */
    protected $runtime;
    
    /**
     * 当前应用的路由实例
     * 
     * @var Router
     */
    protected $router;
    
    /**
     * 当前派发器实例
     * 
     * @var Dispatcher
     */
    protected $dispatcher;
    
    /**
     * 当前运行时的异常处理器
     * 
     * @var ExceptionHandler
     */
    protected $exceptionHandler;
    
    /**
     * 构造函数 引入
     * @param ApplicationBase $app
     * @param Runtime $runtime
     * @param Router $router
     * @param View $view
     * @param Dispatcher $dispatcher
     * @param ExceptionHandler $exceptionHandler
     */
    public function __construct(ApplicationBase $app, Runtime $runtime, Router $router, View $view, Dispatcher $dispatcher, ExceptionHandler $exceptionHandler)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->response = $app->response;
        $this->runtime = $runtime;
        $this->view = $view;
        $this->router = $router;
        $this->dispatcher = $dispatcher;
        $this->exceptionHandler = $exceptionHandler;
        $this->viewDir = TINY_FRAMEWORK_RESOURCE_PATH . 'mvc/view/debug/';
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\Listener\RouteEventListener::onRouterStartup()
     */
    public function onRouterStartup(MvcEvent $event, array $params)
    {
    }
    
    /**
     * 输出框架的文档和手册
     *
     * @return void
     */
    public function showDocsAction()
    {
        $debugDocContent = $this->getDocContent();
        $body = $this->view->fetch('debug/web_docs.htm', [
            'debugDocContent' => $debugDocContent
        ]);
        $this->response->appendBody($body);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\Listener\RouteEventListener::onRouterShutdown()
     */
    public function onRouterShutdown(MvcEvent $event, array $params)
    {
        $cname = $this->request->getControllerName();
        if ($cname != 'debug') {
            return;
        }
        
        try {
            $aname = $this->request->getActionName();
            if ($aname == 'showdocs') {
                $this->showDocsAction();
            }
        } finally
        {
            $this->app->response->end();
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\Listener\DispatchEventListener::onPreDispatch()
     */
    public function onPreDispatch(MvcEvent $event, array $params)
    {
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\Listener\DispatchEventListener::onPostDispatch()
     */
    public function onPostDispatch(MvcEvent $event, array $params)
    {
        $debugInterval = $this->runtime->getRuntimeTotal();
        
        $debugMemory = number_format(memory_get_peak_usage(true) / 1024 / 1024, 4);
        
        // 视图
        $viewPaths = $this->view->getTemplateList();
        $viewAssign = $this->view->getAssigns();
        
        // DB
        $dbQuerys = Db::getQuerys();
        $dbTimeTotal = 0;
        foreach ((array)$dbQuerys as $query) {
            $dbTimeTotal += $query['time'];
        }
        $debugDbQueryTotal = count($dbQuerys);
        
        // 路由
        $router = $this->router->getMatchedRoute();
        if ($router) {
            $routerName = get_class($router);
        }
        $routerUrl = $this->request->uri;
        $routerParams = $this->router->getParams();
        
        // 加载的控制器信息
        $controllerName = $this->request->getControllerName();
        $controllerClass = $this->dispatcher->getControllerClass($controllerName);
        $actionName = $this->request->getActionName();
        $actionMethod  = $this->dispatcher->getActionName($actionName);
        
        // 模型层信息
        $modelList = [];
        $models = [];
        foreach ($models as $model) {
            $modelList[] = get_class($model);
        }
        $modelList = join(' ', $modelList);
        
        // Exception
        $debugExceptions = $this->exceptionHandler->getExceptions();
        
        // DEBUG集合
        $debugs = [
            'app' => $this->app,
            'debug' => $this,
            'debugInterval' => $debugInterval,
            'debugMemory' => $debugMemory,
            'debugViewPaths' => $viewPaths,
            'debugViewAssign' => $viewAssign,
            'debugDbQuerys' => $dbQuerys,
            'debugDbTimeTotal' => $dbTimeTotal,
            'debugDbQueryTotal' => $debugDbQueryTotal,
            'debugRouterName' => $routerName,
            'debugRouterUrl' => $routerUrl,
            'debugRouterParams' => var_export($routerParams, true),
            'debugControllerName' => $controllerClass,
            'debugActionName' => $actionMethod,
            'debugModelList' => $modelList,
            'debugExceptions' => $debugExceptions
        ];
        
        if ($this->app instanceof ConsoleApplication) {
            $body = $this->getConsoleDebugBody($debugs);
            return $this->app->response->appendBody($body);
        }
        
        // web debug输出到console
        if ((bool)$this->app->properties['debug.console']) {
            return $this->outputConsoleDebug($debugs);
        }
        
        // 输出到html后面
        
        // 文档手册信息
        $docsUrl = $this->router->rewriteUrl([
            'c' => 'debug',
            'a' => 'showdocs'
        ]);
        $debugs['debugDocUrl'] = $docsUrl;
        $debugs['debugConstants'] = get_defined_constants(true);
        //$debugs['debugRequestData'] = $this->request->getRequestData();
        $debugs['debugExts'] = get_loaded_extensions();
        $debugs['debugIncludePaths'] = get_include_path() ?: $this->request->server['PATH'];
        $debugs['debugFirstException'] = $this->getFirstException($debugExceptions[0]);
        
        // 附加debug信息到输出
        $body = $this->view->fetch('debug/web.htm', $debugs);
        return $this->app->response->appendBody($body);
    }
    
    /**
     * 输出Debug信息到console
     *
     * @param array $debugs
     * @return bool
     */
    protected function outputConsoleDebug($debugs)
    {
        $debugOutput = $this->getConsoleDebugBody($debugs);
        $body = $this->view->fetch('debug/web_console.htm', [
            'debugOutputConsole' => base64_encode($debugOutput)
        ]);
        $resBody = $this->app->response->getContent();
        if (strpos($resBody, '</head>') > 0 && strpos($resBody, '</title>') > 0) {
            
            $body = preg_replace('/<\/head>/', $body . "\n</head>", $resBody, 1);
            $this->app->response->clear();
        }
        return $this->app->response->appendBody($body);
    }
    
    /**
     * 获取第一个异常的信息
     *
     * @param array|false $firstE
     * @return boolean|array
     */
    protected function getFirstException($firstE)
    {
        if (!$firstE) {
            return false;
        }
        
        $fileLines = file($firstE['file']);
        $currentLine = $firstE['line'];
        $totalLine = count($fileLines);
        $startLine = $currentLine - 7;
        $endLine = $currentLine + 5;
        if ($startLine < 0) {
            $startLine = 0;
        }
        if ($endLine >= $totalLine) {
            $endLine = $totalLine - 1;
        }
        
        $codes = [];
        for ($i = $startLine; $i <= $endLine; $i++) {
            $codes[] = [
                $i + 1,
                $fileLines[$i],
                ($currentLine == $i + 1)
            ];
        }
        $firstE['codes'] = $codes;
        $firstE['traceString'] = str_replace('#', '<br />&nbsp;&nbsp;&nbsp;&nbsp;# File:', $firstE['traceString']);
        return $firstE;
    }
    
    /**
     * 命令行下输出信息
     *
     * @param array $debugs DEBUG信息数组
     * @return string
     */
    protected function getConsoleDebugBody($debugs)
    {
        return $this->view->fetch('debug/console.htm', $debugs);
    }
    
    /**
     * 解析具体文档
     *
     * @return string
     */
    protected function getDocContent()
    {
        $docpath = $this->request->get['docpath'];
        $docpath = \Tiny\Docs\Reader::getDocPath($docpath);
        if (!$docpath) {
            return '';
        }
        $content = $this->view->fetch($docpath, [], true);
        $content = preg_replace_callback(
            "/href=\"(?:https\:\/\/github.com\/opensaasnet\/tinyphp\/blob\/master\/docs\/(.+?)\.md)\"/i",
            [
                $this,
                'parseGithubHref'
            ], $content);
        return $content;
    }
    
    /**
     * 替换掉github.com换成本地域名 加快加载速度
     *
     * @param array $matchs 匹配项数组
     * @return string
     */
    protected function parseGithubHref($matchs)
    {
        return 'href="/index.php?c=debug&a=showdocs&docpath=' . rawurlencode($matchs[1] . '.md') . '"';
    }
}
?>