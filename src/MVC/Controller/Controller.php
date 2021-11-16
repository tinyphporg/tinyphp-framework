<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Controller.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月10日下午11:09:50
 * @Class List
 * @Function List
 * @History King 2017年3月10日下午11:09:50 0 第一次建立该文件
 *          King 2017年3月10日下午11:09:50 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Controller;

/**
 * WEB控制器
 *
 * @package Tiny.Application.Controller
 * @since 2017年3月11日上午12:20:13
 * @final 2017年3月11日上午12:20:13
 */
abstract class Controller extends Base
{

    /**
     * 魔术方式获取属性
     *
     * @param string $key
     * @return mixed session HttpsSession操作对象
     *         cookie HttpCookie操作对象
     */
    protected function _magicGet($key)
    {
        switch ($key)
        {
            case 'cookie':
                return $this->application->getCookie();
            case 'session':
                return $this->application->getSession();
            default:
                return parent::_magicGet($key);
        }
    }
}
?>