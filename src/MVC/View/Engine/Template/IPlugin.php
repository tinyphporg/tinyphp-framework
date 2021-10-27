<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name IPlugin.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年10月19日下午5:28:32 0 第一次建立该文件
 *          King 2021年10月19日下午5:28:32 1 修改
 *          King 2021年10月19日下午5:28:32 stable 1.0.01 审定
 */
namespace Tiny\MVC\View\Engine\Template;

use Tiny\MVC\View\Engine\Template;

/**
 * Template Engine 的插件接口类
 * 
 * @package Tiny.MVC.View.Engine.Template
 * @since  2021年10月19日下午5:28:32
 * @final  2021年10月19日下午5:28:32
 *
 */
interface IPlugin
{    
    /**
     * 设置插件的初始化参数
     * 
     * @param Template $template
     * @param array $config
     * @return boolean
     */
    public function setTemplateConfig(Template $template, array $config);
    
    /**
     * 调用插件事件解析闭合标签
     * 
     * @param string $tagName
     * @return string|FALSE
     */
    public function onParseCloseTag($tagName);
    
    /**
     * 调用插件事件解析tag
     *
     * @param string $tagName  标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加信息
     * @return string|boolean 返回解析成功的字符串  FALSE时没有找到解析成功的插件 或者解析失败
     */
    public function onParseTag($tagName, $tagBody, $extra = NULL);
}
?>