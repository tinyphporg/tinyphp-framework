<?php
/**
 *
 * @Copyright (C), 2011-, King.$i
 * @Name  View.php
 * @Author  King
 * @Version  Beta 1.0
 * @Date: Mon Dec 12 01:34 00 CST 2011
 * @Description
 * @Class List
 *  	1.
 *  @Function List
 *   1.
 *  @History
 *      <author>    <time>                        <version >   <desc>
 *        King      Mon Dec 12 01:34:00 CST 2011  Beta 1.0           第一次建立该文件
 *        King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\View;

use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\View\Engine\ViewEngineInterface;
use Tiny\MVC\Response\Response;
use Tiny\MVC\View\Helper\HelperInterface;
use Tiny\Runtime\Environment;

// 框架默认视图路径
define('TINY_VIEW_TEMPLATE_PATH', dirname(__DIR__) . '/Resources/templates/');

/**
 * 视图层
 *
 * @package Tiny.Application.Viewer
 * @since : Mon Dec 12 01:15 51 CST 2011
 * @final : Mon Dec 12 01:15 51 CST 2011
 */
class View
{
    /**
     * 框架视图模板文件所在路径
     * 
     * @var string
     */
    const VIEW_TEMPLATE_PATH = TINY_VIEW_TEMPLATE_PATH;
    
    /**
     * 当前应用实例
     *
     * @var ApplicationBase
     */
    protected $app;
    
    /**
     * 当前响应实例
     *
     * @var Response
     */
    protected $response;
    
    /**
     * 视图管理器实例
     *
     * @var ViewManager
     */
    protected $viewManager;
    
    /**
     * 视图层预设的值
     *
     * @formatter:on
     * @var array
     */
    protected $variables = [];
    
    /**
     * 模板文件夹
     *
     * @var array
     */
    protected $templateDirs = [];
    
    /**
     * 模板编译存放文件夹
     *
     * @var string
     */
    protected $compileDir;
    
    /**
     * 已解析的模板文件列表
     *
     * @var array
     */
    protected $parsedTemplates = [];
    
    /**
     * 构造函数
     *
     * @param ApplicationBase $app 当前应用实例
     * @param ViewManager $viewManager 当前视图实例
     */
    public function __construct(ApplicationBase $app, ViewManager $viewManager = null)
    {
        
        // default variables
        $this->variables = [
            'view' => $this,
            'env' => $app->get(Environment::class),
            'isDebug' => $app->isDebug,
            'request' => $app->request,
            'response' => $app->response
        ];
        
        // 视图管理器
        if (!$viewManager) {
            $viewManager = new ViewManager($app->container, $this);
        }
        
        //
        $this->viewManager = $viewManager;
        $this->response = $app->response;
    }
    
    /**
     * 视图管理器
     *
     * @return \Tiny\MVC\View\ViewManager
     */
    public function getViewManager()
    {
        return $this->viewManager;
    }
    
    /**
     * 获取模板文件所在目录
     *
     * @return string
     */
    public function getTemplateDir()
    {
        return $this->templateDirs;
    }
    
    /**
     * 设置模板文件所在目录
     *
     * @param string $path 模板文件所在目录路径
     * @return View
     */
    public function setTemplateDir($path, string $templateId = '')
    {
        if (is_array($path)) {
            $this->templateDirs = array_merge($this->templateDirs, $path);
        }
        
        if ($templateId) {
            $this->templateDirs[$templateId] = (string)$path;
        }
        
        if (!in_array(self::VIEW_TEMPLATE_PATH, $this->templateDirs)) {
            $this->templateDirs[] = self::VIEW_TEMPLATE_PATH;
        }

        // 同步给所有实例化的解析器
        $this->viewManager->syncTemplateDir($this->templateDirs);
        return $this;
    }
    
    /**
     * 获取模板文件编译后所在目录
     *
     * @return string
     */
    public function getCompileDir()
    {
        return $this->compileDir;
    }
    
    /**
     * 设置模板编译存放的目录
     *
     * @param string $path 编译后的文件存放目录路径
     * @return View
     */
    public function setCompileDir($path)
    {
        $this->compileDir = $path;
        $this->viewManager->syncCompileDir($path);
        return $this;
    }
    
    /**
     * 获取预编译变量
     *
     * @return array
     */
    public function getVariables($key = null)
    {
        return ($key && key_exists($key, $this->variables)) ? $this->variables[$key] : $this->variables;
    }
    
    /**
     * 添加一个或多个视图变量
     *
     * @param string|array $key 当key为数组时，可添加多个预编译变量
     * @return View
     */
    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->variables = array_merge($this->variables, $key);
        } else {
            $this->variables[$key] = $value;
        }
        $this->viewManager->syncAssign($this->variables);
        return $this;
    }
    
    /**
     * 获取解析过的模板文件
     *
     * @return array
     */
    public function getParsedTemplates()
    {
        return $this->parsedTemplates;
    }
    
    /**
     * 增加一条视图解析记录
     *
     * @param string $templatePath 模板相对路径
     * @param string $templateRealPath 模板真实路径
     * @param string $ename 模板引擎名
     * @param ViewEngineInterface $engineInstance 模板引擎实例
     */
    public function addParsedTemplate($templatePath, $templateRealPath, $engineInstance)
    {
        $this->parsedTemplates[] = [
            $templatePath,
            $templateRealPath,
            get_class($engineInstance),
        ];
    }
    
    /**
     * 解析模板，并将解析后的模板内容注入到application的response中
     *
     * @param string $tpath 模板路径
     * @param boolean $assign 额外的assign变量 仅本次解析生效
     * @param boolean $isAbsolute 模板路径是否为绝对路径
     * @return void
     */
    public function display($tpath, array $assign = [], $templateId = null)
    {
        $content = $this->viewManager->getEngineByPath($tpath)->fetch($tpath, $assign, $templateId);
        $this->response->appendBody($content);
    }
    
    /**
     * 解析模板，并获取解析后的字符串
     *
     * @param string $tpath 模板路径
     * @param boolean $assign 额外的assign变量 仅本次解析生效
     * @param boolean $isAbsolute 是否为绝对的模板路径
     * @return string
     */
    public function fetch($tpath, array $assign = [], $templateId = null)
    {
        return $this->viewManager->getEngineByPath($tpath)->fetch($tpath, $assign, $templateId);
    }
    
    /**
     * 惰性加载 视图助手的实例作为视图层的成员变量
     *
     * @param string $helperName 助手类名
     * @return HelperInterface
     */
    public function __get($name)
    {
        return $this->viewManager->matchHelper($name);
    }
}
?>