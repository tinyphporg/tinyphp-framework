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
     * 实例化的路由器实例
     *
     * @var array
     */
    protected $_routers = [];
    /**
     * 路由器策略集合
     *
     * @var array
     */
    protected $_routerPolicys = [];

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
     *        路由类型名称
     * @param string $className
     *        路由名称
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
     * @param string $driverId
     *        驱动器名称
     * @param string $rule
     *        规则
     * @param array $ruledata
     *        规则附带数据
     * @return void
     */
    public function addRule($driverId, $rule, $data = NULL)
    {
        if (!key_exists($driverId, $this->_driverMaps))
        {
            return FALSE;
        }
        $rule['className'] = $this->_driverMaps[$driverId];
        $rule['data'] = $data;
        $this->_routerPolicys[] = $rule;
    }

    /**
     * 执行路由动作
     *
     * @return void
     */
    public function route()
    {
        $routerString = $this->_req->getRouterString();
        foreach ($this->_routerPolicys as $policy)
        {
            $router = $this->_loadRouter($policy['className']);
            if ($router->checkRule($policy, $routerString))
            {
                return $this->resolveRule($router);
            }
        }
        return FALSE;
    }

    /**
     * 解析规则，并注入到当前应用程序的参数中去
     *
     * @param array $params
     *        参数
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
     * @return \Tiny\MVC\Router\IRouter
     */
    public function getMatchRouter()
    {
        return $this->_matchRouter;
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
     * @param array $rule
     * @return string 规则
     */
    protected function _loadRouter($className)
    {
        static $routers = [];
        $routerId = strtolower($className);

        if (!$routers[$routerId])
        {
            $routers[$routerId] = new $className();
            if (!$routers[$routerId] instanceof IRouter)
            {
                throw new RouterException('router driver:' . $className . ' is not instanceof Tiny\MVC\Router\IRouter');
            }
        }
        $router = $routers[$routerId];
        return $router;
    }
}
?>
