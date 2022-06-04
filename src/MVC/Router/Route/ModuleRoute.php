<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name HttpRoute.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月14日下午9:32:06
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月14日下午9:32:06 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Router\Route;

use Tiny\MVC\Request\Request;
use Tiny\MVC\Request\WebRequest;

/**
 * Http环境下的参数验证
 *
 * @package namespace
 * @since 2022年1月9日上午8:30:59
 * @final 2022年1月9日上午8:30:59
 */
class ModuleRoute implements RouteInterface
{
    
    /**
     * WebRequet
     *
     * @var WebRequest
     */
    protected $request;
    
    /**
     * 解析存放URL参数的数组
     *
     * @var array
     */
    protected $params;
    
    /**
     *
     * {@inheritdoc}
     * @see RouteInterface::match()
     */
    public function match(Request $request, string $routeString, array $routeRule = [])
    {
        $this->params = [];
        $modules = $routeRule['module'];
        
        // string
        if (!is_array($modules)) {
            $this->params['module'] = $modules;
            return true;
        }
        
        // match module regex
        foreach ($modules as $module) {
            if ($matchValue = $this->matchModuleRule($routeString, (string)$module['regex'], (string)$module['value'])){
                $this->params['module'] = $matchValue;
                return true;
            }
        }
        return false;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see RouteInterface::getParams()
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     * 格式化域名 可支持通配符模糊匹配
     *
     * @param string $domain
     * @return string
     */
    protected function matchModuleRule($routeString, $regex, $value)
    {
        if (!$regex || !$value) {
            return false;
        }
        
        $matchs = [];
        if (!preg_match($regex, $routeString, $matchs)) { 
            return false;
        }
        if (false === strpos($value, '$')) {
            return $value;
        }
        return preg_replace_callback('/\$([0-9]+)/', function($ms) use ($matchs){
                    $index = $ms[1];
                    return (key_exists($index, $matchs)) ? $matchs[$index] : $ms[0];
        }, $value);
    }
}
?>