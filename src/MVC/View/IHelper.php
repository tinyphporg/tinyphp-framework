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
 *          King 2021年10月22日下午5:25:07 stable 1.0.01 审定
 */
namespace Tiny\MVC\View;

/**
 * 视图助手接口
 * 
 * @package Tiny.MVC.View.Helper
 * @since 2021年10月22日下午5:25:07
 * @final 2021年10月22日下午5:25:07
 *
 */
interface IHelper
{
    /**
     * 设置View实例
     * 
     * @param View $view
     */
    public function setViewHelperConfig(View $view, array $config);
    
    /**
     * 是否支持指定的helper名检索
     * @param string $hname
     */
    public function matchHelperByName($hname);
}

?>