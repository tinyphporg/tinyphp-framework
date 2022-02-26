<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name FilterInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:08:56
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:08:56 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Filter;

use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;

/**
 * 过滤器接口
 *
 * @package Tiny.Filter
 * @since 2022年2月11日下午12:55:11
 * @final 2022年2月11日下午12:55:11
 */
interface FilterInterface
{
    
    /**
     * 执行过滤
     *
     * @param Request $req 请求实例
     * @param Response $res 响应实例
     */
    public function filter(Request $request, Response $response);
}
?>