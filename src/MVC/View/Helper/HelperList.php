<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Helper.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年10月25日下午5:14:14 0 第一次建立该文件
 *          King 2021年10月25日下午5:14:14 1 修改
 *          King 2021年10月25日下午5:14:14 stable 1.0 审定
 */
namespace Tiny\MVC\View\Helper;

use Tiny\MVC\View\View;
use Tiny\MVC\View\ViewException;
use Tiny\MVC\View\ViewHelperInterface;

/**
 * 视图助手的工具类 根据属性名检索视图层的所有助手并返回实例
 *
 * @package Tiny.MVC.View.Helper
 * @since 2021年10月25日下午5:14:14
 * @final 2021年10月25日下午5:14:14
 *       
 *       
 */
class HelperList implements ViewHelperInterface, \ArrayAccess
{

    /**
     * 可检索的，作为视图实例的属性的名称
     *
     * @var array
     */
    const HELPER_NAME_LIST = ['helper'];

    /**
     * View 当前view实例
     *
     * @var View
     */
    protected $view;

    /**
     * 配置
     *
     * @var array
     */
    protected $config;

    /**
     * 设置View实例
     *
     * @param View $view
     */
    public function setViewHelperConfig(View $view, array $config)
    {
        $this->view = $view;
        $this->config = $config;
    }

    /**
     * 检测作为视图实例的属性的名称
     *
     * @param string $hname
     */
    public function matchHelperByName($hname)
    {
        return in_array($hname, self::HELPER_NAME_LIST);
    }

    /**
     * 查找该助手名是否存在
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($hname)
    {
        return $this->view->{$hname} ? true : false;
    }

    /**
     * 获取该助手名指定的实例
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($hname)
    {
        return $this->view->{$hname};
    }

    /**
     * 不允许设置该助手名指定的视图助手实例
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($hname, $instance)
    {
        throw new ViewException('没有权限重设View中实现了\Tiny\MVC\View\Helper\IHelper接口的属性');
    }

    /**
     * 不允许销毁该助手名指定的视图助手实例
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($hname)
    {
        throw new ViewException('没有权限销毁View中实现了\Tiny\MVC\View\Helper\IHelper接口的属性');
    }
}
?>