<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name PHP.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月12日下午3:37:03
 * @Class List
 * @Function List
 * @History King 2013年3月12日下午3:37:03 0 第一次建立该文件
 *          King 2017年3月12日下午3:37:03 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\ViewException;

/**
 * 原生的PHP解析引擎
 *
 * @package Tiny\MVC\View\Engine
 * @since 2013-5-25上午08:22:54
 * @final 2017-3-12上午08:22:54
 */
class PHP extends ViewEngine
{
    /**
     * 支持匹配解析的扩展名文件
     *
     * @var array
     */
    protected $extendNames = ['php'];
    
    /**
     * 
     * @param string $tpath
     * @param boolean $isAbsolute
     * @throws ViewException
     * @return string
     */
    public function getCompiledFile($tpath, $templateId = null)
    {
        $pathinfo  = $this->getTemplateRealPath($tpath, $templateId);
        if (!$pathinfo)
        {
            throw new ViewException(sprintf("viewer error: file %s is not a file", $tpath));
        }
        return $pathinfo['path'];
    }
}
?>