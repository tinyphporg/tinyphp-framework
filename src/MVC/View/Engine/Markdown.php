<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Markdown.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年10月28日下午5:58:06 0 第一次建立该文件
 *          King 2021年10月28日下午5:58:06 1 修改
 *          King 2021年10月28日下午5:58:06 stable 1.0 审定
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\ViewException;

/**
 * 注解文件解析器
 *
 * @package Tiny.MVC.View.Engine
 * @since 2022年2月15日下午3:29:24
 * @final 2022年2月15日下午3:29:24
 */
class Markdown extends ViewEngine
{
    /**
     * 支持匹配解析的扩展名文件
     *
     * @var array
     */
    protected $extendNames = ['md'];
    
    /**
     * Parsedown实例
     *
     * @var \Parsedown
     */
    protected $parsedownInstance;
    
    /**
     * \
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\View\Engine\ViewEngine::getCompiledFile()
     */
    public function getCompiledFile($tpath, $templateId = null)
    {
        $pathinfo = $this->getTemplateRealPath($tpath, $templateId);
        if (!$pathinfo) {
            throw new ViewException(sprintf("viewer error: file %s is not a file", $tpath));
        }
        $tfile = $pathinfo['path'];
        $tfilemtime = $this->app->isDebug ? filemtime($tfile) : $pathinfo['mtime'];
        
        // 如果开启模板缓存 并且 模板存在且没有更改
        $compilePath = $this->createCompileFilePath($tfile);
        if (((extension_loaded('opcache') && opcache_is_script_cached($compilePath)) || file_exists($compilePath)) && (filemtime($compilePath) > $tfilemtime)) {
            return $compilePath;
        }
        $compileContent = $this->parseTemplateFile($pathinfo['path']);
        file_put_contents($compilePath, $compileContent, LOCK_EX);
        return $compilePath;
    }
    
    /**
     * 解析模板文件
     * 
     * @param string $tfile 模板文件路径
     * @throws ViewException
     * @return string
     */
    protected function parseTemplateFile($tfile)
    {
        $fh = fopen($tfile, 'rb');
        if (!$fh) {
            throw new ViewException("viewer error: fopen $tfile is faild");
        }
        flock($fh, LOCK_SH);
        $filesize = filesize($tfile);
        $template = $filesize > 0 ? fread($fh, $filesize) : '';
        flock($fh, LOCK_UN);
        fclose($fh);
        return $this->parseMarkdown($template);
    }
    
    /**
     * 解析markdown文档
     *
     * @param string $template 模板字符串
     * @return string
     */
    protected function parseMarkdown($template)
    {
        $parsedownInstance = $this->getParsedownInstance();
        return $parsedownInstance->parse($template);
    }
    
    /**
     * 获取Parsedown实例
     *
     * @return \Parsedown
     */
    protected function getParsedownInstance()
    {
        if (!$this->parsedownInstance) {
            $parsedownInstance = new \Parsedown();
            $parsedownInstance->setSafeMode(true);
            $parsedownInstance->setMarkupEscaped(true);
            $this->parsedownInstance = $parsedownInstance;
        }
        return $this->parsedownInstance;
    }
    
    /**
     * 生成一个编译模板文件的文件名
     *
     * @param string $tfile 输入的编译模板路径
     * @return string
     */
    protected function createCompileFilePath($tfile)
    {
        return $this->compileDir . md5($tfile) . '.markdown.php';
    }
}
?>