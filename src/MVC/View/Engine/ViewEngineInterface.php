<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name IViewer.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月13日上午12:17:34
 * @Class List
 * @Function List
 * @History King 2017年3月13日上午12:17:34 0 第一次建立该文件
 *          King 2017年3月13日上午12:17:34 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\View;

/**
 * 视图模板引擎接口
 *
 * @package Tiny.Application.Viewer
 * @since : Mon Dec 12 01:06 15 CST 2011
 * @final : Mon Dec 12 01:06 15 CST 2011
 */
interface ViewEngineInterface
{
    /**
     * 增加匹配的扩展名
     *
     * @param string|array $extendName
     */
    public function addExtendName($extendName);
    
    /**
     * 是否匹配对应的扩展名
     *
     * @param string $extendName 扩展名
     *
     * @return boolean true 匹配|false 不匹配
     */
    public function matchExtendName(string $extendName);
    
    
    /**
     * 设置模板变量
     *
     * @param string $key 键名 为Array时可设置多个参数名
     * @param mixed $value 值
     * @return bool
     */
    public function assign($key, $value = null);
    
    /**
     * 输出模板解析后的数据
     *
     * @param string $file 文件路径
     * @param bool $isAbsolute 是否为绝对路径
     * @return string
     */
    public function fetch($filepath, array $assigns = [], $templateId = null);
    
    /**
     * 设置模板存放路径
     *
     * @param string $path
     */
    public function setTemplateDir($path);
    
    /**
     * 设置模板编译后的存放路径
     *
     * @param string $path
     */
    public function setCompileDir($path);
}
?>