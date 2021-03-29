<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name PathInfo.php
 * @author King
 * @version Beta 1.0
 * @Date 2019年11月20日下午7:28:25
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2019年11月20日下午7:28:25 第一次建立该文件
 *          King 2019年11月20日下午7:28:25 修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 *
 */
namespace Tiny\MVC\Router;

/**
 * 路径路由
 *
 * @package Tiny.MVC.Router
 * @since 2019年11月26日下午2:07:07
 * @final 2019年11月26日下午2:07:07
 */
class PathInfo implements IRouter
{

    /**
     * 解析存放URL参数的数组
     *
     * @var array
     */
    protected $_params = [];

    /**
     * 检查该规则是否成功
     *
     * @param array $regRule
     *        匹配规则
     * @param string $routerString
     *        路由名称
     * @return bool
     */
    public function checkRule(array $regRule, $routerString)
    {
        $ext = $regRule['ext'];
        if (!$out = $this->_checkUrl($ext, $routerString))
        {
            return FALSE;
        }
        list($c, $this->_params['a']) = $out;
        if ($c[0] == "/" || $c[0] == "\\")
        {
            $c = substr($c, 1);
        }
        $this->_params['c'] = $c;
        return TRUE;
    }

    /**
     * 检测URL是否符合路由规则
     *
     * @access protected
     * @param string $ext
     *        扩展名
     * @param string $routerString
     *        路由字符串
     * @return mixed
     *
     */
    protected function _checkUrl($ext, $routerString)
    {
        $pattern = "/^((\/[a-z]+)*)(\/([a-z]+))(\/|" . $ext . ")?$/i";
        $index = strpos($routerString, "?");
        if ($index)
        {
            $routerString = substr($routerString, 0, $index);
        }

        $out = NULL;
        if (!preg_match($pattern, $routerString, $out))
        {
            return FALSE;
        }

        if (!$out[1 && !$ext])
        {
            $c = $out[4];
            return [
                $c,
                ''
            ];
        }
        $c = $out[1];
        if ($ext && $out[5] == $ext)
        {
            $a = $out[4];
        }
        elseif ($out[5] == '/')
        {
            $c .= $out[3];
            $a = 'index';
        }
        else
        {
            $a = $out[4];
        }

        return [
            $c,
            $a
        ];
    }

    /**
     * 获取路由解析后的URL参数
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }
}
?>