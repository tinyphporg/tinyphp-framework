<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name RouteInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月14日下午9:30:53
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月14日下午9:30:53 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Router\Route;

use Tiny\MVC\Request\Request;

/**
 * 路由器接口
 *
 * @package Tiny.MVC.Router.Route
 * @since 2017年3月12日下午5:57:08
 * @final 2017年3月12日下午5:57:08
 */
interface RouteInterface
{
    
    /**
     * 检查规则是否符合当前path
     *
     * @param array $regRule 注册规则
     * @param string $routerString 路由规则
     * @return bool
     */
    public function match(Request $request, string $routeString, array $rule = []);
    
    /**
     * 获取解析后的参数，如果该路由不正确，则不返回任何数据
     *
     * @return array|null
     */
    public function getParams(): array;
}
?>