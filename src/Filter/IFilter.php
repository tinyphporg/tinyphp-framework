<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name IFilter.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月9日下午9:18:52
 * @Class List
 * @Function List
 * @History King 2017年3月9日下午9:18:52 0 第一次建立该文件
 *          King 2017年3月9日下午9:18:52 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Filter;

use Tiny\MVC\Request\Base as Request;
use Tiny\MVC\Response\Base as Response;

/**
 * 过滤器接口
 *
 * @package Tiny.Filter
 * @since 2017年3月9日下午9:18:52
 * @final 2017年3月9日下午9:18:52
 */
interface IFilter
{

    /**
     * 执行过滤
     * @param Request $req 请求实例
     * @param Response $res 响应实例
     */
    public function doFilter(Request $req, Response $res);
}

?>