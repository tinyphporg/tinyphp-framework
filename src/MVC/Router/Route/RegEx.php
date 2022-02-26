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
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\Router\Route;

use Tiny\MVC\Request\Request;

/**
 * 路由规则实现
 *
 * @package Tiny.MVC.Router
 * @since : Thu Dec 15 17 42 00 CST 2011
 * @final : Thu Dec 15 17 42 00 CST 2011
 */
class RegEx implements RouteInterface
{
    
    /**
     * 解析存放URL参数的数组
     *
     * @var array
     */
    protected $params = [];
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Router\Route\RouteInterface::match()
     */
    public function match(Request $request, string $routeString, array $routeRule = [])
    {
        $regex = (string)$routeRule['regex'];
        $regKeys = (array)$routeRule['keys'];
        if (!preg_match($regex, $routeString, $matchs)) {
            return false;
        }
        foreach ($regKeys as $key => $valueName) {
            $valueName = preg_replace_callback('/\$([0-9]{1,2})/',
                function ($ms) use ($matchs) {
                    $index = $ms[1];
                    if (key_exists($index, $matchs)) {
                        return $matchs[$index];
                    }
                }, $valueName);
            
            $this->params[$key] = $valueName;
        }
        return true;
    }
    
    /**
     * 获取路由解析后的URL参数
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
?>