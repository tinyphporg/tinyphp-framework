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

class Markdown extends Base
{
    /**
     * Parsedown实例
     * 
     * @var \Parsedown
     */
    protected $_parsedownInstance;
    
    /**
     * 获取编译后的文件路径
     *
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Base::getCompileFile()
     */
    public function getCompiledFile($tpath, $isAbsolute = FALSE)
    {
        $tfile  = $this->_getTemplateRealPath($tpath, $isAbsolute);
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
    protected function _fetchCompiledContent($compileFile, $assign = FALSE)
    {
        $template  = file_get_contents($compileFile);
        if (!$template)
        {
            return '';
        }
        return $this->_parseMarkdown($template);
    }
    
    /**
     * 解析markdown文档
     * 
     * @param string $template 模板字符串
     * @return string
     */
    protected function _parseMarkdown($template)
    {
        $parsedownInstance = $this->_getParsedownInstance();
        return $parsedownInstance->parse($template);
    }
    
    /**
     * 获取Parsedown实例
     * 
     * @return \Parsedown
     */
    protected function _getParsedownInstance()
    {
        if (!$this->_parsedownInstance)
        {
            $parsedownInstance = new \Parsedown();
            $parsedownInstance->setSafeMode(TRUE);
            $parsedownInstance->setMarkupEscaped(TRUE);
            $this->_parsedownInstance = $parsedownInstance;
        }
        return $this->_parsedownInstance;
    }
}
?>