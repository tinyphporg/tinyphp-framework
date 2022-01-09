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
use Tiny\MVC\Request\Param\Readonly;
use Tiny\MVC\Request\WebRequest;
use Tiny\DI\ContainerInterface;

/**
 * 路由器接口
 *
 * @package Tiny.MVC.Router
 * @since 2017年3月12日下午5:57:08
 * @final 2017年3月12日下午5:57:08
 */
interface RouteInterface
{
    
    /**
     * 检查规则是否符合当前path
     *
     * @param array $regRule 注册规则
     * @param string $routerString 路由规则
     * @return bool
     */
    public function match(Request $request, string $routeString, array $rule = []);
    
    /**
     * 获取解析后的参数，如果该路由不正确，则不返回任何数据
     *
     * @return array|NULL
     */
    public function getParams(): array;
    
    /**
     *
     * @param array $params
     */
    public function rewriteUri(Request $request, array $params, $isRewrited = true);
}

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
    // @formatter:on
    
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
    public function addRoute(string $name, $className)
    {
        if (!key_exists($name, $this->routeFactoryConfig)) {
            return false;
        }
        $this->routeFactoryConfig[] = $className;
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
        $name = (string)$rule['route'];
        if (!key_exists($name, $this->routeFactoryConfig)) {
            if (!$rule['class']) {
                return false;
            }
            $this->addRoute($name, $rule['class']);
        }
        
        $ruleConfig = [];
        if (key_exists('rule', $rule) && is_array($rule['rule'])) {
            $ruleConfig = $rule['rule'];
        }
        
        $routeRule = [];
        $routeRule['name'] = $name;
        $routeRule['class'] = $this->routeFactoryConfig[$name];
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
        $routeString = $this->request->getUri();
        if (!$routeString || $routeString === '/') {
            return false;
        }
        
        if ($this->request instanceof WebRequest) {
            $httpRoute = $this->factory(HttpRoute::class);
            if (!$httpRoute->match($this->request,$routeString)) {
                return false;
            }
            $this->params += $httpRoute->getParams();
        }
        
        foreach ($this->routeChain as $routeConfig) {
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
     * @return \Tiny\MVC\Router\IRouter | NULL
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
        
        $cp = $request->getControllerParam();
        $ap = $request->getActionParam();
        
        $controllerName = (isset($params[$cp])) ? $params[$cp] : $request->getController();
        $actionName = (isset($params[$ap])) ? $params[$ap] : $request->getAction();
        
        if ($isRewrited && $this->matchedRoute) {
            
            $rparams = $params;
            unset($rparams[$cp], $rparams[$ap]);
            $uri = $this->_matchedRouter->rewriteUri($controllerName, $actionName, $rparams);
            if ($uri) {
                return $host . $uri;
            }
        }
        $url = $host . $this->_req->scriptName;
        $u = array();
        foreach ($params as $k => $v) {
            $u[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $url .= '?' . join('&', $u);
        return $url;
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
            $cp = $this->request->getControllerParam();
            $params[$cp] = $params['controller'];
            $this->request->setController($params['controller']);
        }
        
        if (key_exists('action', $params))
        {
            $ap = $this->request->getActionParam();
            $params[$ap] = $params['action'];
            $this->request->setAction($params['action']);
        }
        
        $this->request->setParam($params);
        $this->params = $params;
        return $this->params;
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

/**
 * Http环境下的参数验证
 *
 * @package namespace
 * @since 2022年1月9日上午8:30:59
 * @final 2022年1月9日上午8:30:59
 */
class HttpRoute implements RouteInterface
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
     * @see \Tiny\MVC\Router\RouteInterface::match()
     */
    public function match(Request $request, string $routeString, array $routeRule = [])
    {
        return true;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Router\RouteInterface::getParams()
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Router\RouteInterface::rewriteUri()
     */
    public function rewriteUri(Request $request, array $params = [], $isRewrited = true)
    {
    }
    
    /**
     * 格式化域名 可支持通配符模糊匹配
     *
     * @param string $domain
     * @return string
     */
    protected function _isMatchedDomain($routerDomain)
    {
        $domain = $this->_req->host;
        if (!$routerDomain) {
            return TRUE;
        }
        foreach ($routerDomain as $rd) {
            $rd = preg_replace(['/\./', '/[\*]+/', '/\?/'], ['\.', '.*', '.{1}'], $rd);
            if (preg_match('/^' . $rd . '$/i', $domain)) {
                return TRUE;
            }
        }
        return FALSE;
    }
}
?>
