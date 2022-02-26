<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name WebFilter.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月9日下午9:21:05
 * @Class List
 * @Function List
 * @History King 2017年3月9日下午9:21:05 0 第一次建立该文件
 *          King 2017年3月9日下午9:21:05 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Filter;

use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;

/**
 * Web过滤器
 *
 * @package Tiny.Filter
 * @since 2017年3月9日下午9:21:05
 * @final 2017年3月9日下午9:21:05
 */
class WebFilter implements FilterInterface
{
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Filter\FilterInterface::filter()
     */
    public function filter(Request $request, Response $response)
    {
    }
    
    /**
     * 去除XSS注入
     *
     * @param array $data
     * @return array
     */
    public function formatXSS($data)
    {
        return htmlspecialchars($data);
    }
}
?>