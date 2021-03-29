<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name IRouter.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月10日下午6:45:57
 * @Class List
 * @Function List
 * @History King 2017年3月10日下午6:45:57 0 第一次建立该文件
 *          King 2017年3月10日下午6:45:57 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\Router;

/**
 * 路由器接口
 *
 * @package Tiny.MVC.Router
 * @since 2017年3月12日下午5:57:08
 * @final 2017年3月12日下午5:57:08
 */
interface IRouter
{

    /**
     * 检查规则是否符合当前path
     *
     * @param array $regRule
     *        注册规则
     * @param string $routerString
     *        路由规则
     * @return bool
     */
    public function checkRule(array $regRule, $routerString);

    /**
     * 获取解析后的参数，如果该路由不正确，则不返回任何数据
     *
     * @return array|NULL
     */
    public function getParams();
}
?>