<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name Tagger.php
 * @author King
 * @version stable 2.0
 * @Date 2022年12月6日下午4:16:23
 * @Class List class
 * @Function List function_container
 * @History King 2022年12月6日下午4:16:23 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Engine\Tagger;

use Tiny\MVC\View\Engine\ViewEngine;
use Tiny\MVC\View\View;

define('TINY_IS_IN_VIEW_ENGINE_TEMPLATE', true);
/**
* tinyphp自带的标签式模板引擎
* @package Tiny.MVC.View.Engin.Tagger
* @since 2022年12月6日下午4:17:23
* @final 2022年12月6日下午4:17:23
*/
class Tagger extends ViewEngine
{

    /**
     * 视图管理器
     * 
     * @var View
     */
    protected $view;
    
    /**
     * 配置数组
     * 
     * @var array
     */
    protected $config = [];
    
    /**
     * 解析器管理器
     * 
     * @var ParserManager
     */
    protected $parserManager;
    
    /**
     * 插件实例
     *
     * @var array
     */
    protected $pluginInstances = [];
    

    
    /**
     * 解析中的模板标签
     *
     * @var array
     */
    protected $parsedTemplateTags = [];
    
    /**
     * 存放的模板标签解析内容数组
     *
     * @var array
     */
    protected $templateTagContents = [];
    
    /**
     * 构造函数
     * 
     */
    public function __construct(View $view, array $config = [])
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
        $this->view = $view;
    }
    
    /**
     * 获取解析器管理器
     * 
     * @return ParserManager
     */
    protected function getParserManager()
    {
        if (!$this->parserManager) {
            $parserConfig = [
                'config' => (array)$this->config['parsers'],
                Tagger::class => $this,
            ];
            $this->parserManager =  $this->view->getViewManager()->getOrCreateInstance(ParserManager::class, $parserConfig); 
        }
        return $this->parserManager;
    }
    
    
    /**
     * 获取模板解析后的文件路径
     *
     * @param string $file 文件路径
     * @param bool $isAbsolute 是否绝对位置
     * @return string 编译后的文件路径
     */
    public function getCompiledFile($tpath, $templateId = null)
    {
        $tpath = preg_replace('/\/+/', '/', $tpath);
        $pathinfo = $this->getTemplateRealPath($tpath, $templateId);
        if (!$pathinfo) {
            throw new TaggerException(sprintf("viewer error: the template %s is not exists!", $tpath));
        }
        
        $tfile = $pathinfo['path'];
        $tfilemtime = $this->app->isDebug ? filemtime($tfile) : $pathinfo['mtime'];
        
        // 如果开启模板缓存 并且 模板存在且没有更改
        $compilePath = $this->createCompileFilePath($tfile);
        if (((extension_loaded('opcache') && opcache_is_script_cached($compilePath)) || file_exists($compilePath)) && (filemtime($compilePath) > $tfilemtime)) {
         // return $compilePath;
        }
        
        // 读取模板文件
        $fh = fopen($tfile, 'rb');
        if (!$fh) {
            throw new TaggerException("viewer error: fopen $tfile is faild");
        }
        
        flock($fh, LOCK_SH);
        $fsize = filesize($tfile);
        $templateContent =  ($fsize > 0) ? fread($fh, $fsize) : '';
        flock($fh, LOCK_UN);
        fclose($fh);
        
        // 解析模板并写入编译文件
        $compileContent = $this->parseTemplate($templateContent);
        $ret = file_put_contents($compilePath, $compileContent, LOCK_EX);
        if (false === $ret || !is_file($compilePath)) {
            throw new TaggerException(sprintf("viewer compile error: file_put_contents %s is faild", $compilePath));
        }
        return $compilePath;
    }
    
   public function fetch($tpath, array $assign = [], $templateId = null)
    {
        $this->templateTagContents = [];
        $content = parent::fetch($tpath, $assign, $templateId);//
        $this->fetchedTemplateTagContent($content);
        return $content;
    }
    
    /**
     * 生成一个编译模板文件的文件名
     *
     * @param string $tfile 输入的编译模板路径
     * @return string
     */
    protected function createCompileFilePath($tfile)
    {
        return $this->compileDir . md5($tfile) . '.tag.php';
    }
    
    /**
     * 解析模板文件
     *
     * @param string $template 待解析的模板字符串
     * @return string
     *
     */
    protected function parseTemplate($template)
    {
        return $this->getParserManager()->parseTemplate($template);
    }
    
    /**
     * 解析模板标签
     *
     * @param string $tfile 模板
     * @param string $templateId
     * @param string $name
     * @param string $inject
     * @return string
     */
    protected function fetchTemplate($tpath, $templateId = null, $name = '', $isSelf = false)
    {
        // content
        $content = '';
        if ($tpath) {
            if ($isSelf) {
                $compileFile = $this->getCompiledFile($tpath);
                $content = $this->fetchCompiledContent($compileFile);
            } else {
                $content = $this->view->fetch($tpath, $assigns, $templateId);
            }
        }
        
        if (!$name) {
            return $content;
        }
        
        // merge
        if (!key_exists($name, $this->templateTagContents)) {
            $this->templateTagContents[$name] = '';
        }
        
        $this->templateTagContents[$name] .= $content;
        return '';
    }
    
    /**
     * 全局替换占位符
     *
     * @param string $content
     */
    public function fetchedTemplateTagContent(&$content)
    {
        while (preg_match('/<\!\-\-template\.content\.\w+\-\->/is', $content)) {
            $content = preg_replace_callback("/<\!\-\-template\.content\.(\w+)\-\->/is", [
                $this,
                'onFetchedTemplateTagContent'
            ], $content);
        }
    }
    
    /**
     * 合并输出内容
     *
     * @param array $matchs
     * @return string
     */
    protected function onFetchedTemplateTagContent($matchs)
    {
        $name = $matchs[1];
        if (!key_exists($name, $this->templateTagContents)) {
            return '';
        }
        
        // 输出
        $content = $this->templateTagContents[$name];
        unset($this->templateTagContents[$name]);
        return $content;
    }
    

}
?>