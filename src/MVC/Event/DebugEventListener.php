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
namespace Tiny\MVC\Event;

use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\View\View;
use Tiny\Runtime\Runtime;
use Tiny\Data\Db\Db;
use Tiny\MVC\Router\Router;
use Tiny\MVC\Application\ConsoleApplication;
use Tiny\MVC\Controller\Dispatcher;
use Tiny\Runtime\ExceptionHandler;
use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;
use Tiny\MVC\Controller\DispatcherException;
use Tiny\MVC\Application\WebApplication;
use Tiny\DI\ContainerInterface;
use Tiny\Runtime\Environment;
use Tiny\MVC\Application\Properties;

/**
 * 调试模式处理器
 *
 * @package Tiny.MVC.Event
 * @since 2022年8月15日下午3:16:33
 * @final 2022年8月15日下午3:16:33
 */
class DebugEventListener implements RequestEventListenerInterface, DispatchEventListenerInterface
{
    
    /**
     * WEB环境下允许的debug动作
     */
    const WEB_ACTIONS = [
        'showdocs'
    ];
    
    const CONSOLE_ACTIONS = [
        'help',
        'clearcache',
        'clear',
        'clearlog',
        'clearview',
        'splitlog',
    ];
    
    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * 引入当前应用实例
     *
     * @param ApplicationBase $app
     */
    public function __construct(ApplicationBase $app)
    {
        $this->app = $app;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\RouteEventListenerInterface::onRouterStartup()
     */
    public function onPreDispatch(MvcEvent $event, array $params)
    {
        if (!$this->app->isDebug) {
            return;
        }
        
        $cname = $this->app->request->getControllerName();
        if ($cname !== 'debug') {
            return;
        }
        
        $aname = $this->app->request->getActionName();
        $this->dispatch($aname);
        $this->app->response->end();
    }
    
    /**
     * 执行
     * 
     * @param string $cname 
     * @param string $aname
     */
    protected function dispatch($aname)
    {
        if ($this->app instanceof ConsoleApplication && !in_array($aname, self::CONSOLE_ACTIONS)) {
            return;
        }
        if ($this->app instanceof WebApplication && !in_array($aname, self::WEB_ACTIONS)) {
            return;
        }

        $actionName = $aname. 'Action';
        if (!method_exists($this, $actionName)) {
            return;
        }
        $view = $this->app->get(View::class);
        $container = $this->app->get(ContainerInterface::class);
        return $container->call([$this, $actionName]);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\RouteEventListenerInterface::onRouterShutdown()
     */
    public function onPostDispatch(MvcEvent $event, array $params)
    {

    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\RequestEventListenerInterface::onBeginRequest()
     */
    public function onBeginRequest(MvcEvent $event, array $params)
    {
    }
    
    /**
     * 附加调试信息
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Event\DispatchEventListenerInterface::onPostDispatch()
     */
    public function onEndRequest(MvcEvent $event, array $params)
    {
        if (!$this->app->isDebug) {
            return;
        }
        
        // @formatter:off
        $debugInfo = $this->getDebugInfo(
            $this->app->get(Runtime::class), 
            $this->app->get(ExceptionHandler::class), 
            $this->app, $this->app->request, 
            $this->app->response, 
            $this->app->getRouter(), 
            $this->app->getDispatcher(), 
            $this->app->getView()
        );
        // @formatter:on
        
        if ($this->app instanceof ConsoleApplication) {
            $body = $this->getConsoleDebugBody($debugInfo);
            return $this->app->response->appendBody($body);
        }
        
        // web debug输出到console
        if ((bool)$this->app->properties['debug.console']) {
            return $this->outputConsoleDebug($debugInfo);
        }
        
        // 附加debug信息到输出
        $body = $this->app->getView()->fetch('debug/web.htm', $debugInfo);
        return $this->app->response->appendBody($body);
    }
    
    /**
     * 构建debug输出信息数组
     *
     * @param Runtime $runtime
     * @param ExceptionHandler $exceptionHandler
     * @param ApplicationBase $app
     * @param Request $request
     * @param Response $response
     * @param Router $router
     * @param Dispatcher $dispatcher
     * @param View $view
     * @return []
     */
    protected function getDebugInfo(Runtime $runtime, $exceptionHandler, $app, $request, $response, $router, $dispatcher, $view)
    {
        // default info
        $debugInterval = $runtime->getLifetime();
        $debugIncludeFiles = get_included_files();
        $debugMemory = number_format(memory_get_peak_usage(true) / 1024 / 1024, 4);
        
        // 视图
        $viewPaths = $view->getParsedTemplates();
        $viewAssign = $view->getVariables();
        
        // DB
        $dbQuerys = Db::getQuerys();
        $dbTimeTotal = 0;
        foreach ((array)$dbQuerys as $query) {
            $dbTimeTotal += $query['time'];
        }
        $debugDbQueryTotal = count($dbQuerys);
        
        // 路由
        if ($route = $router->getMatchedRoute()) {
            $routerName = get_class($route);
        }
        $routerUrl = $request->uri;
        $routerParams = $router->getParams();
        
        // 加载的控制器信息
        $controllerName = $request->getControllerName();
        $moduleName = $request->getModuleName();
        if ($controllerName && $moduleName) {
            try {
                $controllerClass = $dispatcher->getControllerClass($controllerName, $moduleName);
            } catch (DispatcherException $e) {
                $controllerClass = '';
            }
        }
        $actionName = $request->getActionName();
        $actionMethod = $dispatcher->getActionName($actionName);
        
        // 模型层
        $modelList = [];
        $models = [];
        foreach ($models as $model) {
            $modelList[] = get_class($model);
        }
        $modelList = join(' ', $modelList);
        
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
            'debugExceptions' => $this->formatExceptions($exceptionHandler)
        ];
        $debugs['debugFirstException'] = $this->formatFirstException($exceptionHandler);
        
        if (!$app instanceof WebApplication) {
            return $debugs;
        }
        
        
        
        // 文档手册信息
        $docsUrl = $router->rewriteUrl([
            'c' => 'debug',
            'a' => 'showdocs'
        ]);
        $debugs['debugDocUrl'] = $docsUrl;
        $debugs['debugConstants'] = get_defined_constants(true);
        $debugs['debugExts'] = get_loaded_extensions();
        $debugs['debugIncludeFiles'] = $debugIncludeFiles;
        $debugs['debugIncludePaths'] = get_include_path() ?: $request->server['PATH'];
        return $debugs;
    }
    
    /**
     * 显示帮助信息
     * @param View $view
     */
    public  function helpAction(View $view)
    {
        $view->display('debug/console_help.htm');
    }
    
    public function clearAction()
    {
        $this->dispatch('clearcache');
        $this->dispatch('clearlog');
        $this->dispatch('clearview');
    }
    /**
     * 清理缓存
     * 
     * @param Environment $env 当前环境实例
     * @return void
     */
    public function clearCacheAction(Environment $env)
    {
        $cacheDir = $env['TINY_CACHE_PATH'];
        if (false === $this->clearfiles($cacheDir)) {
            $this->dispatch('help');
        }
    }
    
    /**
     * 清理日志
     *
     * @param Environment $env 当前环境实例
     * @return void
     */
    public function clearLogAction(Environment $env)
    {
        $logDir = $env['TINY_LOG_PATH'];
        if (false === $this->clearfiles($logDir)) {
            $this->dispatch('help');
        }
    }
    
    /**
     * 清理视图文件
     * 
     * @param Properties $prop
     */
    public function clearViewAction(Properties $prop)
    {
        $viewdir = $prop['view.compile'];
        $viewCacheDir = $prop['view.cache'];
        $this->clearfiles($viewdir);
        $this->clearfiles($viewCacheDir);
        
    }
    
    /**
     * 清理文件
     * 
     * @param string $dir 文件夹路径
     */
    protected function clearfiles($dir)
    {
        if (!$dir || in_array($dir, ['/', '.', '..', '/bin', '/var', '/usr', '/root', '/home', '/tmp'])) {
            return false;
        }
        $files = scandir($dir);
        foreach($files as $f) {
            if ($f == '.' || $f == '..' || $f == '.keepgit') {
                continue;
            }
            $p  = $dir . $f;
            if (is_dir($p)) {
                $this->clearfiles($p . '/');
            }else if(is_file($p)) {
                echo "delete $p\n";
                unlink($p);
            }
        }
    }
    
    /**
     * 输出框架的文档和手册
     *
     * @return void
     */
    public function showDocsAction(View $view)
    {
        // @formatter:off
        $docContent = $this->getDocContent();
        $view->display('debug/web_docs.htm', ['debugDocContent' =>  $docContent]);
        // @formatter:on
    }
    
    /**
     * 解析具体文档
     *
     * @return string
     */
    protected function getDocContent()
    {
        //
        $docpath = $this->app->request->get['docpath'];
        $docpath = \Tiny\Docs\Reader::getDocPath($docpath);
        if (!$docpath) {
            return '';
        }
        
        // format content
        $content = $this->app->getView()->fetch($docpath, [], true);
        $content = preg_replace_callback("/href=\"(?:https\:\/\/github.com\/tinyphporg\/tinyphp-docs\/(?:blob|edit|tree)\/master\/docs\/(.+?)\.md)\"/i", function ($matchs) {
            return 'href="/index.php?c=debug&a=showdocs&docpath=' . rawurlencode($matchs[1] . '.md') . '"';
        }, $content);
        return $content;
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
        $body = $this->app->getView()->fetch('debug/web_console.htm', [
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
     * 格式化异常信息
     *
     * @param ExceptionHandler $exceptionHandler
     */
    protected function formatExceptions($exceptionHandler)
    {
        $exceptions = $exceptionHandler->getExceptions();
        $exceptionList = [];
        foreach ($exceptions as $exception) {
            $exceptionList[] = str_replace('#', '    # File:', $exception->getTraceAsString());
        }
        return $exceptionList;
    }
    
    /**
     * 获取格式化的第一个异常信息
     *
     * @param ExceptionHandler $exceptionHandler
     * @return []
     */
    protected function formatFirstException($exceptionHandler)
    {
        $exceptions = $exceptionHandler->getExceptions();
        $firstE = $exceptions[0];
        
        if (!$firstE) {
            return false;
        }
        $filePath = $firstE->getFile();
        $codes = [];
        if (is_file($filePath)) {
            $fileLines = file($firstE->getFile());
            $currentLine = $firstE->getLine();
            $totalLine = count($fileLines);
            $startLine = $currentLine - 7;
            $endLine = $currentLine + 5;
            if ($startLine < 0) {
                $startLine = 0;
            }
            if ($endLine >= $totalLine) {
                $endLine = $totalLine - 1;
            }
            
            for ($i = $startLine; $i <= $endLine; $i++) {
                $codes[] = [
                    $i + 1,
                    $fileLines[$i],
                    ($currentLine == $i + 1)
                ];
            }
        }
        $exception = [];
        $exception['type'] = $exceptionHandler->getExceptionName($firstE->getCode());
        $exception['handler'] = get_class($firstE);
        $exception['message'] = $firstE->getMessage();
        $exception['file'] = $firstE->getFile();
        $exception['line'] = $firstE->getLine();
        $exception['codes'] = $codes;
        $exception['traceString'] = str_replace('#', '<br />&nbsp;&nbsp;&nbsp;&nbsp;# File:', $firstE->getTraceAsString());
        return $exception;
    }
    
    /**
     * 命令行下输出信息
     *
     * @param array $debugs DEBUG信息数组
     * @return string
     */
    protected function getConsoleDebugBody($debugs)
    {
        return $this->app->getView()->fetch('debug/console.htm', $debugs);
    }
}
?>