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
use Tiny\Data\Db\DbException;

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
        'showdocs'
    ];

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
    protected $_viewDir;

    /**
     * 初始化
     *
     * @param $app \Tiny\MVC\ApplicationBase
     *            当前应用实例
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
        $debugDocContent = $this->_getDocContent();
        $body = $this->_view->fetch('debug/web_docs.htm', [ 'debugDocContent' => $debugDocContent]);
        $this->_app->response->appendBody($body);
    }

    /**
     * 解析具体文档
     *
     * @return string
     */
    protected function _getDocContent()
    {
        $docpath = $this->_request->get['docpath'];
        $docpath = \Tiny\Docs\Reader::getDocPath($docpath);
        if (!$docpath)
        {
            return '';
        }
        $content = $this->_view->fetch($docpath, [], TRUE);
        $content = preg_replace_callback("/href=\"(?:https\:\/\/github.com\/saasjit\/tinyphp\/blob\/master\/docs\/(.+?)\.md)\"/i", [ $this, '_parseGithubHref'], $content);
        return $content;
    }

    /**
     * 替换掉github.com换成本地域名 加快加载速度
     *
     * @param array $matchs
     *            匹配项数组
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
        if (!$this->_app->isDebug)
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
        $this->_initDebugView();

        $debugInterval = microtime(TRUE) - $this->_startTime;
        $debugMemory = number_format(memory_get_peak_usage(TRUE) / 1024 / 1024, 4);

        // 视图
        $viewPaths = $this->_view->getTemplateList();
        $viewAssign = $this->_view->getAssigns();

        // DB
        $dbQuerys = Db::getQuerys();
        $dbTimeTotal = 0;
        foreach ((array)$dbQuerys as $query)
        {
            $dbTimeTotal += $query['time'];
        }
        $debugDbQueryTotal = count($dbQuerys);

        // 路由
        $router = $this->_app->getRouter()->getMatchedRouter();
        if ($router)
        {
            $routerName = get_class($router);
        }
        $routerUrl = $this->_app->request->getRouterString();
        $routerParams = $this->_app->getRouter()->getParams();

        // 控制器信息
        $controllerList = [];
        $controllers = $this->_app->getControllerList();
        foreach ($controllers as $cpath => $controller)
        {
            $controllerList[] = $cpath . '(' . get_class($controller) . ')';
        }
        $controllerList = join(' ', $controllerList);

        // 模型层信息
        $modelList = [];
        $models = $this->_app->getModels();
        foreach ($models as $model)
        {
            $modelList[] = get_class($model);
        }
        $modelList = join(' ', $modelList);

        // Exception
        $debugExceptions = ExceptionHandler::getInstance()->getExceptions();

        // DEBUG集合
        $debugs = [
            'app' => $this->_app,
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
            'debugRouterParams' => $routerParams,
            'debugControllerList' => $controllerList,
            'debugModelList' => $modelList,
            'debugExceptions' => $debugExceptions
        ];
        
        if ($this->_app->env->isRuntimeConsoleMode())
        {
            $body = $this->_getConsoleDebugBody($debugs);
            return $this->_app->response->appendBody($body);
        }
        
        // web debug输出到console
        if ((bool)$this->_app->properties['debug.console'])
        {
            return $this->_outputConsoleDebug($debugs);
        }

        // 输出到html后面
        
        // 文档手册信息
        $docsUrl = $this->_app->getRouter()->rewriteUrl([
            'c' => 'debug',
            'a' => 'showdocs'
        ]);
        $debugs['debugDocUrl'] = $docsUrl;
        $debugs['debugConstants'] = get_defined_constants(TRUE);
        $debugs['debugRequestData'] = $this->_app->request->getRequestData();
        $debugs['debugExts'] = get_loaded_extensions();
        $debugs['debugIncludePaths'] = get_include_path();
        $debugs['debugFirstException'] = $this->_getFirstException($debugExceptions[0]);
        
        // 附加debug信息到输出
        $body = $this->_view->fetch('debug/web.htm', $debugs);
        return $this->_app->response->appendBody($body);
    }

    
    /**
     * 输出Debug信息到console
     * 
     * @param array $debugs
     * @return bool
     */
    protected function _outputConsoleDebug($debugs)
    {
        $debugOutput = $this->_getConsoleDebugBody($debugs);
        $body = $this->_view->fetch('debug/web_console.htm', [
            'debugOutputConsole' => base64_encode($debugOutput)
        ]);
        $resBody = $this->_app->response->getContent();
        if (strpos($resBody, '</head>') > 0 && strpos($resBody, '</title>') > 0)
        {
            
            $body = preg_replace('/<\/head>/', $body . "\n</head>", $resBody, 1);
            $this->_app->response->clear();
        }
        return $this->_app->response->appendBody($body);
    }

    /**
     * 获取第一个异常的信息
     *
     * @param array|FALSE $firstE
     * @return boolean|array
     */
    protected function _getFirstException($firstE)
    {
        if (!$firstE)
        {
            return FALSE;
        }

        $fileLines = file($firstE['file']);
        $currentLine = $firstE['line'];
        $totalLine = count($fileLines);
        $startLine = $currentLine - 7;
        $endLine = $currentLine + 5;
        if ($startLine < 0)
        {
            $startLine = 0;
        }
        if ($endLine >= $totalLine)
        {
            $endLine = $totalLine - 1;
        }

        $codes = [];
        for ($i = $startLine; $i <= $endLine; $i++)
        {
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

    protected function _getWebDebugBody($debugs)
    {
    }

    /**
     * 命令行下输出信息
     *
     * @param array $debugs
     *            DEBUG信息数组
     * @return string
     */
    protected function _getConsoleDebugBody($debugs)
    {
        return $this->_view->fetch('debug/console.htm', $debugs);
    }

    /**
     * 初始化debug
     *
     * @param
     *            void
     * @return View
     */
    protected function _initDebugView()
    {
        if (!$this->_view)
        {
            $this->_request = $this->_app->request;
            $this->_view = $this->_app->getView();
            $this->_viewDir = TINY_MVC_RESOURCES . 'views/debug/';
        }
        return $this->_view;
    }
}
?>