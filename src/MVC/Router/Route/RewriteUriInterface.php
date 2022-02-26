<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name RewriteUriInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月14日下午10:34:07
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月14日下午10:34:07 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Router\Route;

/**
* 重写URI接口
* 
* @package Tiny.MVC.Router.Route
* @since 2022年2月14日下午10:34:53 
* @final 2022年2月14日下午10:34:53 
*/
interface RewriteUriInterface 
{
    /**
     *
     * @param array $params
     */
    public function rewriteUri(string $controllerName, string $actionName, array $params = []);
}
?>