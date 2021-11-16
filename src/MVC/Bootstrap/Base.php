<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月12日下午5:31:11
 * @Class List
 * @Function List
 * @History King 2017年3月12日下午5:31:11 0 第一次建立该文件
 *          King 2017年3月12日下午5:31:11 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Bootstrap;

use Tiny\MVC\ApplicationBase;

/**
 * 引导基类
 *
 * @package Tiny.Application.Bootstrap
 * @since 2017年3月12日下午5:34:17
 * @final 2017年3月12日下午5:34:17
 */
abstract class Base
{

    /**
     * 当前App运行实例
     *
     * @var ApplicationBase
     */
    protected $_application;

    /**
     * 可以初始化调用的函数
     *
     * @var array
     */
    private $_methods = [];

    /**
     * 执行引导程序初始化函数
     *
     * @param
     *        void
     * @return void
     */
    final public function bootstrap(ApplicationBase $app)
    {
        $this->_application = $app;
        $methods = $this->_getBootstrapMethods();
        foreach ($methods as $method)
        {
            call_user_func_array([
                $this,
                $method
            ], [
                'application' => $app
            ]);
        }
    }

    /**
     * 获取可供初始化执行的函数数组
     *
     * @return array
     */
    final private function _getBootstrapMethods()
    {
        if ($this->_methods)
        {
            return $this->_methods;
        }

        $ms = get_class_methods($this);
        foreach ($ms as $method)
        {
            if (0 !== stripos($method, 'init'))
            {
                continue;
            }
            $this->_methods[] = $method;
        }
        return $this->_methods;
    }
}
?>