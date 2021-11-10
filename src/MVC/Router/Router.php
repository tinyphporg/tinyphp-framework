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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\Router;

use Tiny\MVC\Request\Base as Request;

/**
 * 路由器主体类
 *
 * @package Tiny.MVC.Router
 * @since : Thu Dec 15 09 22 30 CST 2011
 * @final : Thu Dec 15 09 22 30 CST 2011
 *          2021年10月29日11:58 修改router的domain匹配 rewriteUrl
 */
class Router
{

    /**
     * 路由驱动类的集合数组
     *
     * @var array
     */
    protected $_routerMaps = [
        'regex' => '\Tiny\MVC\Router\RegEx',
        'pathinfo' => '\Tiny\MVC\Router\PathInfo'
    ];

    /**
     * 是否为命令行形式下的路由模式
     * 
     * @var bool
     */
    protected $_isConsoleMode;
    
    /**
     * 当前Http应用程序的请求对象
     *
     * @var Request
     */
    protected $_req;

    /**
     * 路由器配置集合
     *
     * @var array
     */
    protected $_routerRules = [];

    /**
     * 路由器实例集合
     *
     * @var array
     */
    protected $_routers = [];

    /**
     * 是否已经执行过路由检测
     *
     * @var bool
     */
    protected $_isRouted = FALSE;

    /**
     * 匹配的路由实例
     *
     * @var IRouter
     */
    protected $_matchRouter;

    /**
     * 解析的参数
     *
     * @var array
     */
    protected $_params = [];

    /**
     * 注册路由
     *
     * @param string $routerId 注册的路由器ID
     * @param string $routerName 实现了IRouter接口的路由器类名
     * @return bool
     */
    public function regRouter($routerId, $routerName)
    {
        if (!key_exists($routerId, $this->_routerMaps))
        {
            return FALSE;
        }
        $this->_routerMaps[$routerId] = $routerName;
        return TRUE;
    }

    /**
     * 构造函数
     *
     * @param Request $req 由当前app实例注入的request实例
     * @return void
     */
    public function __construct(Request $req, $isConsoleMode = FALSE)
    {
        $this->_req = $req;
        $this->_isConsoleMode = $isConsoleMode;
    }

    /**
     * 添加路由的匹配规则
     *
     * @param array $rule
     *             routerId string 注册$this->_routermaps内的key
     *             router  string 实现了IRouter接口的路由器类名
     *             ruleData string 附加的匹配规则数据
     *             damain array  可通配匹配的域名 默认为空
     * @return boolean
     */
    public function addRule(array $rule)
    {
        $routerId = (string)$rule['router'];
        if (!key_exists($routerId, $this->_routerMaps))
        {
            return FALSE;
        }
        $domain = [];
        if (key_exists('domain', $rule) && $rule['domain'])
        {
            $domain = is_array($rule['domain']) ? $rule['domain'] : [
                (string)$rule['domain']
            ];
        }
        $ruleData = [];
        if (key_exists('rule', $rule) && is_array($rule['rule']))
        {
            $ruleData = $rule['rule'];
        }
        
        $routerRule = [];
        $routerRule['routerId'] = $routerId;
        $routerRule['routerName'] = $this->_routerMaps[$routerId];
        $routerRule['ruleData'] = $ruleData;
        $routerRule['domain'] = $domain;
        $this->_routerRules[] = $routerRule;
        return TRUE;
    }

    /**
     * 执行路由动作
     *
     * @return void
     */
    public function route($routerString)
    {
        if (!$routerString || $routerString === '/')
        {
            return FALSE;
        }
        foreach ($this->_routerRules as $routerRule)
        {
            $routerName = $routerRule['routerName'];
            if (!$routerName)
            {
                continue;
            }
            if ($this->_isConsoleMode && !$this->_isMatchedDomain($routerRule['domain']))
            {
                continue;
            }
            $routerInstance = $this->_getRouterInstance($routerName);
            if ($routerInstance->checkRule($routerRule['ruleData'], $routerString))
            {
                return $this->_resolveMatchedRouter($routerInstance);
            }
        }
        return FALSE;
    }

    /**
     * 获取匹配的router实例 
     *
     * @return \Tiny\MVC\Router\IRouter | NULL
     */
    public function getMatchedRouter()
    {
        return $this->_matchedRouter;
    }

    /**
     * 获取解析Url而来的参数
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_matchedRouter ? $this->_params : [];
    }

    /**
     * 根据输入参数重写URL
     *
     * @param array $params
     *            GET参数
     * @param boolean $isRewrite
     *            是否让匹配的IRouter实例重写URI部分
     * @return string
     */
    public function rewriteUrl(array $params, $isRewrite = FALSE)
    {
        $host = ($this->_req->ishttps ? 'https://' : 'http://') . $this->_req->host;
        $cp = $this->_req->getControllerParam();
        $ap = $this->_req->getActionParam();
        $controllerName = (isset($params[$cp])) ? $params[$cp] : $this->_req->getController();
        $actionName = (isset($params[$ap])) ? $params[$ap] : $this->_req->getAction();
        if ($isRewrite && $this->_matchedRouter)
        {

            $rparams = $params;
            unset($rparams[$cp], $rparams[$ap]);
            $uri = $this->_matchedRouter->rewriteUri($controllerName, $actionName, $rparams);
            if ($uri)
            {
                return $host . $uri;
            }
        }
        $url = $host . $this->_req->scriptName;
        $u = array();
        foreach ($params as $k => $v)
        {
            $u[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $url .= '?' . join('&', $u);
        return $url;
    }
    
    /**
     * 解析规则，并注入到当前应用程序的参数中去
     *
     * @param array $params
     *            参数
     * @return void
     */
    protected function _resolveMatchedRouter(IRouter $router)
    {
        $this->_matchedRouter = $router;
        $this->_params = $router->getParams();
        $this->_req->setRouterParam($this->_params);
        return $router;
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
        if (!$routerDomain)
        {
            return TRUE;
        }
        foreach ($routerDomain as $rd)
        {
            $rd = preg_replace(['/\./','/[\*]+/','/\?/'], ['\.', '.*', '.{1}'], $rd);
            if (preg_match('/^' . $rd . '$/i', $domain))
            {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * 获取路由对象
     *
     * @param array $routerName
     *            路由器的类名
     * @return string IRouter
     */
    protected function _getRouterInstance($routerName)
    {
        if (key_exists($routerName, $this->_routers))
        {
            return $this->_routers[$routerName];
        }

        if (!class_exists($routerName))
        {
            throw new RouterException(sprintf('router driver:%s is not exists!', $routerName));
        }

        $routerInstance = new $routerName();
        if (!$routerInstance instanceof IRouter)
        {
            throw new RouterException(sprintf('router driver:%s is not instanceof Tiny\MVC\Router\IRouter', $routerName));
        }
        $this->_routers[$routerName] = $routerInstance;
        return $routerInstance;
    }
}
?>
