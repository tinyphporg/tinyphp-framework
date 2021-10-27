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
 */
class Router
{

    /**
     * 路由驱动类的集合数组
     *
     * @var array
     */
    protected $_driverMaps = [
        'regex' => '\Tiny\MVC\Router\RegEx',
        'pathinfo' => '\Tiny\MVC\Router\PathInfo'
    ];

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
     * 注册路由驱动
     *
     * @param string $type
     *            路由类型名称
     * @param string $className
     *            路由名称
     * @return bool
     */
    public function regDriver($type, $className)
    {
        if (!key_exists($type, $this->_driverMaps))
        {
            return FALSE;
        }
        $this->_driverMaps[$type] = $className;
    }

    /**
     * 构造函数
     *
     * @param Request $req
     * @return void
     */
    public function __construct(Request $req)
    {
        $this->_req = $req;
    }

    /**
     * 添加路由规则
     *
     * @param array 路由规则数组
     * @return boolean
     */
    public function addRule(array $rule)
    {
        $routerId = (string)$rule['router'];
        if (!key_exists($routerId, $this->_driverMaps))
        {
            return FALSE;
        }
        
        // 域名
        $domain = [];
        if(key_exists('domain', $rule) && $rule['domain'])
        {  
            $domain = is_array($rule['domain']) ? $rule['domain'] : [(string)$rule['domain']];
        }
        
        // 数据配置
        $ruleData = [];
        if(key_exists('rule', $rule) && is_array($rule['rule']))
        {
            $ruleData = $rule['rule'];
        }
        
        $routerRule = [];
        $routerRule['routerId'] = $routerId;
        $routerRule['router'] = $this->_driverMaps[$routerId];
        $routerRule['data']  = $ruleData;
        $routerRule['domain'] = $domain;
        $this->_routerRules[] = $routerRule;
    }

    /**
     * 执行路由动作
     *
     * @return void
     */
    public function route()
    {
        $routerString = $this->_req->getRouterString();
        
              
        foreach ($this->_routerRules as $routerRule)
        {
            $routerName = $routerRule['router'];
            if(!$routerName)
            {
                continue;
            }
            if(!$this->_checkDomain($routerRule['domain']))
            {
                continue;
            }
            $routerInstance = $this->_getRouterInstance($routerName);
            if ($routerInstance ->checkRule($routerRule['data'], $routerString))
            {
                return $this->resolveRule($routerInstance);
            }
        }
        return FALSE;
    }
    
    /**
     * 预算可以将军
     * 
     * @param array $routerDomain
     * @return boolean
     */
    protected function _checkDomain($routerDomain)
    {
        $domain = $this->_req->host;
        if(!$routerDomain)
        {
            return TRUE;
        }
        foreach($routerDomain as & $rd)
        {
            $rd = '/^' . preg_replace(['/\./', '/[\*]+/'], ['\.', '.*'], $rd) . '$/i';
            echo $rd,$domain;
            if(preg_match($rd, $domain))
            {
                echo 'aaaaaaa';
                return TRUE;
            }
        }
        
        return FALSE;
    }

    /**
     * 解析规则，并注入到当前应用程序的参数中去
     *
     * @param array $params
     *            参数
     * @return void
     */
    public function resolveRule(IRouter $router)
    {
        $this->_matchRouter = $router;
        $this->_params = $router->getParams();
        $this->_req->setRouterParam($this->_params);
    }

    /**
     * 获取匹配的router实例
     *
     * @return \Tiny\MVC\Router\IRouter
     */
    public function getMatchRouter()
    {
        return $this->_matchRouter;
    }

    /**
     */
    public function rewriteUrl(array $params, $isRewrite = FALSE)
    {
        $host = ($this->_req->ishttps ? 'https://' : 'http://') . $this->_req->host;
        $cp = $this->_req->getControllerParam();
        $ap = $this->_req->getActionParam();
        $controllerName = (isset($params[$cp])) ? $params[$cp] : $this->_req->getController();
        $actionName = (isset($params[$ap])) ? $params[$ap] : $this->_req->getAction();
        print_r($this->_matchRouter);
        if ($isRewrite && $this->_matchRouter)
        {
            
            $rparams = $params;
            unset($rparams[$cp], $rparams[$ap]);
            $uri = $this->_matchRouter->rewriteUri($controllerName, $actionName, $rparams);
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
     * 获取解析Url而来的参数
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_matchRouter ? $this->_params : [];
    }

    /**
     * 获取路由对象
     *
     * @param array $routerName 路由器的类名
     * @return string IRouter
     */
    protected function _getRouterInstance($routerName)
    {
        if(key_exists($routerName, $this->_routers))
        {
            return $this->_routers[$routerName];
        }
        
        if(!class_exists($routerName))
        {
            throw new RouterException(sprintf('router driver:%s is not exists!', $routerName));
        }
        
        $routerInstance = new $routerName();
        if (!$routerInstance instanceof IRouter)
        {
            throw new RouterException(sprintf('router driver:%sis not instanceof Tiny\MVC\Router\IRouter', $routerName));
        }
        $this->_routers[$routerName] = $routerInstance;
        return $routerInstance;
    }
}
?>
