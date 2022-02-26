<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Router.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月8日下午4:20:28
 * @Class List
 * @Function List
 * @History King 2017年3月8日下午4:20:28 0 第一次建立该文件
 *          King 2017年3月8日下午4:20:28 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Router;

use Tiny\MVC\Request\Request;
use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Router\Route\HttpRoute;
use Tiny\MVC\Router\Route\RouteInterface;
use Tiny\MVC\Router\Route\PathInfo;
use Tiny\MVC\Router\Route\RewriteUriInterface;
use Tiny\MVC\Router\Route\RegEx;


/**
 * 路由器主体类
 *
 * @package Tiny.MVC.Router
 * @since : Thu Dec 15 09 22 30 CST 2011
 * @final : Thu Dec 15 09 22 30 CST 2011
 *        2021年10月29日11:58 修改router的domain匹配 rewriteUrl
 */
class Router
{
    
    /**
     * 路由提供者集合
     * name => routeInterface
     * @formatter:off
     * @var array
     */
    protected $routeFactoryConfig = [
       'regex' => RegEx::class, 
        'pathinfo' => PathInfo::class,
    ];
    
    /**
     * 路由器实例集合
     *
     * @var array
     */
    protected $routes = [];
    
    /**
     * 路由器配置集合
     *
     * @var array
     */
    protected $routeChain = [];
    
    /**
     * 匹配的路由实例
     *
     * @var RouteInterface
     */
    protected $matchedRoute;
    
    /**
     * 是否已经执行过路由检测
     *
     * @var bool
     */
    protected $isRouted = false;
    
    /**
     * 解析的参数
     *
     * @var array
     */
    protected $params = [];
    
    /**
     * 当前请求实例
     * 
     * @var Request
     */
    protected $request;
    
    /**
     * 构造函数
     * 
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * 注册路由
     *
     * @param string $routerId 注册的路由器ID
     * @param string $routerName 实现了IRouter接口的路由器类名
     * @return bool
     */
    public function addRoute(string $routeName, $routeClass)
    {
        if (!key_exists($routeName, $this->routeFactoryConfig)) {
            return false;
        }
        $this->routeFactoryConfig[$routeName] = $routeClass;
    }
    
    /**
     * 添加路由的匹配规则
     *
     * @param array $rule routerId string 注册$this->_routermaps内的key
     *        router string 实现了IRouter接口的路由器类名
     *        ruleData string 附加的匹配规则数据
     *        damain array 可通配匹配的域名 默认为空
     * @return boolean
     */
    public function addRouteRule(array $rule)
    {
        $routeNname = (string)$rule['route'];
        if (!key_exists($routeNname, $this->routeFactoryConfig)) {
            return false;
        }
        
        $ruleConfig = (key_exists('rule', $rule) && is_array($rule['rule'])) ? $rule['rule'] : [];
        $routeRule = [];
        $routeRule['name'] = $routeNname;
        $routeRule['class'] = $this->routeFactoryConfig[$routeNname];
        $routeRule['rule'] = $ruleConfig;
        $this->routeChain[] = $routeRule;
        return true;
    }
    
    /**
     * 执行路由动作
     *
     * @return void
     */
    public function route()
    {
        $routeString = $this->request->getRouteContext();
        if (!$routeString || $routeString === '/') {
            return false;
        }
        
        $httpRoute = ($this->request instanceof WebRequest) ? $this->factory(HttpRoute::class) : null;
        foreach ($this->routeChain as $routeConfig) {
            if ($httpRoute && !$httpRoute->match($this->request, $routeString, $routeConfig['rule'])) {
                continue;
            }
            $route = $this->factory($routeConfig['class']);
            if ($route->match($this->request, $routeString, $routeConfig['rule'])) {
                $this->resolveMatchedRoute($route);
                return $route;
            }
        }
        return false;
    }
    
    /**
     * 获取匹配的router实例
     *
     * @return RouteInterface
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }
    
    /**
     * 获取解析Url而来的参数
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params ? $this->params : [];
    }
    
    /**
     * 根据输入参数重写URL
     *
     * @param array $params GET参数
     * @param boolean $isRewrite 是否让匹配的IRouter实例重写URI部分
     * @return string
     */
    public function rewriteUrl(array $params, $isRewrited = true)
    {
        
        $request = $this->request;
        if (!$request instanceof WebRequest) {
            return;
        }

        $host = ($request->ishttps ? 'https://' : 'http://') . $request->host;        
        $cp = $request->getControllerParamName();
        $ap = $request->getActionParamName();
        $controllerName = (isset($params[$cp])) ? $params[$cp] : $request->getControllerName();
        $actionName = (isset($params[$ap])) ? $params[$ap] : $request->getActionName();
        
        // rewrite uri
       
        if ($isRewrited && $this->matchedRoute && $this->matchedRoute instanceof RewriteUriInterface) {
            
            $rparams = $params;
            unset($rparams[$cp], $rparams[$ap]);
            $uri = $this->matchedRoute->rewriteUri($controllerName, $actionName, $params);
            return $host . $uri;
        }
        
        $url = $host . $this->request->scriptName;
        $urlParams = [];
        foreach ($params as $k => $v) {
            $urlParams[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        return $url . '?' . join('&', $urlParams);
    }
    
    /**
     * 解析规则，并注入到当前应用程序的参数中去
     *
     * @param array $params 参数
     * @return void
     */
    protected function resolveMatchedRoute(RouteInterface $route)
    {
        $this->matchedRoute = $route;
        $params = $this->params + $route->getParams();
        
        if (key_exists('controller', $params))
        {
            $cp = $this->request->getControllerParamName();
            $params[$cp] = $params['controller'];
            $this->request->setControllerName($params['controller']);
            unset($params['controller']);
        }
        
        if (key_exists('action', $params))
        {
            $ap = $this->request->getActionParamName();
            $params[$ap] = $params['action'];
            $this->request->setActionName($params['action']);
            unset($params['action']);
        }
        
        $this->request->setRouteParam($params);
        $this->params = $params;
        return $params;
    }
    
    /**
     * 获取路由对象
     *
     * @param string $routeName 路由器的类名
     * @return RouteInterface
     */
    protected function factory(string $routeName)
    {
        if (key_exists($routeName, $this->routes)) {
            return $this->routes[$routeName];
        }
        
        if (!class_exists($routeName)) {
            throw new RouterException(sprintf('router driver:%s is not exists!', $routeName));
        }
        
        $routerInstance = new $routeName();
        if (!$routerInstance instanceof RouteInterface) {
            throw new RouterException(sprintf('router driver:%s is not instanceof Tiny\MVC\Router\RouterInterface', $routeName));
        }
        
        $this->routes[$routeName] = $routerInstance;
        return $routerInstance;
    }
}
?>
