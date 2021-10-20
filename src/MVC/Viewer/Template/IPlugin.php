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
namespace Tiny\MVC\Viewer\Template;

interface IPlugin
{    
    public function onTagMatch($match);
}
?>