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
     * Parsedown实例
     * 
     * @var \Parsedown
     */
    protected $parsedownInstance;
    
    /**
     * \
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\ViewEngine::getCompiledFile()
     */
    public function getCompiledFile($tpath, $isAbsolute = false)
    {
        $tfile  = $this->getTemplateRealPath($tpath, $isAbsolute);
        if (!$tfile)
        {
            throw new ViewException(sprintf("viewer error: file %s is not a file", $tfile));
        }
        return $tfile;
    }
    
    /**
     * 通过模板文件的真实路径获取文件内容
     *
     * @param string $tfile
     * @param mixed $assign
     * @return string
     */
    protected function fetchCompiledContent($compileFile, $assign = false)
    {
        $template  = file_get_contents($compileFile);
        if (!$template)
        {
            return '';
        }
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
        if (!$this->parsedownInstance)
        {
            $parsedownInstance = new \Parsedown();
            $parsedownInstance->setSafeMode(true);
            $parsedownInstance->setMarkupEscaped(true);
            $this->parsedownInstance = $parsedownInstance;
        }
        return $this->parsedownInstance;
    }
}
?>