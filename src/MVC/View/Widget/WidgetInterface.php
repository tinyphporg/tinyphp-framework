<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name WidgetInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年12月12日下午12:08:36
 * @Class List class
 * @Function List function_container
 * @History King 2022年12月12日下午12:08:36 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Widget;

/**
* 视图部件接口
* 
* @package Tiny.MVC.View.Widget
* @since 2022年12月12日下午12:12:23
* @final 2022年12月12日下午12:12:23
*/
interface WidgetInterface
{    
    /**
     * 解析部件标签
     * @param string $tagName
     * @param array $params
     */
    public function parseTag(array $params = []);
    
    /**
     * 解析部件的闭合标签
     * 
     * @param string $tagName
     * @param array $params
     */
    public function parseCloseTag();
}
?>