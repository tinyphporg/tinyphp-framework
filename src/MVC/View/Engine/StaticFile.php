<?php 
/**
 * 静态模板解析类
 * 
 * @copyright (C), 2013-, King.
 * @name Static.php
 * @author King
 * @version stable 2.0
 * @Date 2022年6月10日上午10:27:47
 * @Class List class
 * @Function List function_container
 * @History King 2022年6月10日上午10:27:47 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\ViewException;

/**
* 
* @package namespace
* @since 2022年6月10日上午10:30:25
* @final 2022年6月10日上午10:30:25
*/
class StaticFile extends ViewEngine
{
    
    /**
     * \
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\ViewEngine::getCompiledFile()
     */
    public function getCompiledFile($tpath, $templateId = null)
    {
        $tfile  = $this->getTemplateRealPath($tpath, $templateId);
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
}
?>