<?php
/**
 *
 * @copyright (C), 2011-, King
 * @name RouterRule.php
 * @author King
 * @version Beta 1.0
 * @Date 2013-4-1下午03:41:59
 * @Description 路由规则
 * @Class List
 *        1.
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King 2013-4-1下午03:41:59 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 *
 */
namespace Tiny\MVC\Router;


/**
 * 路由规则实现
 *
 * @package Tiny.MVC.Router
 * @since : Thu Dec 15 17 42 00 CST 2011
 * @final : Thu Dec 15 17 42 00 CST 2011
 */
class RegEx implements IRouter
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
     * @param $request \Tiny\MVC\Request\Base
     * @return bool
     */
    public function checkRule(array $regRule, $routerString)
    {
        $reg = $regRule['reg'];
        $regArray = $regRule['keys'];

        $out = NULL;
        if (!preg_match($reg, $routerString, $out))
        {
            return FALSE;
        }

        foreach ($regArray as $key => $value)
        {
            $v = $out[$value];
            if (strpos($v, '/') > -1)
            {
                $v = explode('/', $v);
                foreach ($v as & $vi)
                {
                    $vi = ucfirst($vi);
                }
                $v = join('', $v);
            }
            $this->_params[$key] = $v;
        }
        return true;
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
    
    public function rewriteUri($controllerName, $actionName, array $params)
    {
        
    }
}
?>