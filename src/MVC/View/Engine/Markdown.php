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
 *          King 2021年10月28日下午5:58:06 stable 1.0.01 审定
 */

namespace Tiny\MVC\View\Engine;


use Tiny\MVC\View\ViewException;

class Markdown extends Base
{
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
        $variables = is_array($assign) ? array_merge($this->_variables, $assign) : $this->_variables;
        extract($variables, EXTR_SKIP);
        $parsdownInstance = new \Parsedown();
        $parsdownInstance->setSafeMode(true);
        $parsdownInstance->setMarkupEscaped(true);
        $content = $parsdownInstance->parse(file_get_contents($compileFile));
        return $content;
    }
    
    /**
     * 输出编译后的内容
     *
     * @param string $compileFile
     * @return void
     */
    protected function _displayCompiledContent($compileFile, $assign = FALSE)
    {
        return;
        $variables = is_array($assign) ? array_merge($this->_variables, $assign) : $this->_variables;
        extract($variables, EXTR_SKIP);
    }
}
?>