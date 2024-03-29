<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name IHelper.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年10月22日下午5:25:07 0 第一次建立该文件
 *          King 2021年10月22日下午5:25:07 1 修改
 *          King 2021年10月22日下午5:25:07 stable 1.0 审定
 */
namespace Tiny\MVC\View\Helper;

use Tiny\MVC\View\View;

/**
 * 视图助手接口
 * 
 * @package Tiny.MVC.View.Helper
 * @since 2021年10月22日下午5:25:07
 * @final 2021年10月22日下午5:25:07
 *
 */
interface HelperInterface
{
    /**
     * 是否支持指定的helper名检索
     * 
     * @param string $helperName 助手名称
     */
    public function matchHelperName(string $helperName);
}

?>