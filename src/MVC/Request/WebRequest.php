<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name HttpRequest.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月8日下午4:33:02
 * @Class List
 * @Function List
 * @History King 2017年3月8日下午4:33:02 0 第一次建立该文件
 *          King 2017年3月8日下午4:33:02 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Request;

use Tiny\MVC\Request\Param\Get;
use Tiny\MVC\Request\Param\Post;
use Tiny\Runtime\Param\Readonly;
use Tiny\MVC\Web\HttpCookie;
use Tiny\MVC\Web\HttpSession;

/**
 * Web请求
 *
 * @package Tiny.MVC.Request
 * @since 2017年3月8日下午4:33:02
 * @final 2017年3月8日下午4:33:02
 */
class WebRequest extends Request
{

    /**
     * $_GET 只读
     * @var Get
     */
    public $get;
    
    /**
     * $_POST 只读
     * 
     * @var POST
     */
    public $post;
    
    /**
     * 
     * @var 
     */
    public $file;
    
    /**
     * 完整资源描述
     * 
     * @var string
     */
    public $url;
    
    /**
     * 脚本名
     * 
     * @var string
     */
    public $scriptName;
    
    /**
     * 服务端域名
     * @var string
     */
    public $host;
    
    /**
     * 服务端口
     * @var int
     */
    public $port;
    
    /**
     * 相对资源定位
     * 
     * @var string
     */
    public $uri;
    
    /**
     * 客户端IP
     * 
     * @var string
     */
    public $ip;
    
    /**
     * 是否为加密HTTP连接
     * 
     * @var bool
     */
    public $isHttps;
    
    /**
     * 请求方式 POST GET PUT DELETE
     * 
     * @var string
     */
    public $method;
    
    /**
     * 是否为POST
     * 
     * @var bool
     */
    public $isPost;
    
    /**
     * 用户请求标识
     * 
     * @var string
     */
    public $userAgent;
    
    /**
     * 脚本根目录
     * 
     * @var string
     */
    public $rootDir;
    
    /**
     * 来源链接
     * 
     * @var string
     */
    public $referer;
    
    /**
     * URL的pathinfo信息
     * 
     * @var string
     */
    public $pathinfo;
    
    /**
     * 请求参数字符串
     * 
     * @var string
     */
    public $queryString;
    
    /**
     * cookie操作类
     * 
     * @var HttpCookie
     */
   // public $cookie;
    
    /**
     * Session操作类
     * 
     * @var HttpSession
     */
   // public $session;
    
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\Request\Request::setRouteParam()
     */
    public function setRouteParam(array $params)
    {
        parent::setRouteParam($params);
        $this->get->merge($params);
    }
    
    /**
     *  
     * @param string $key
     * @return mixed|\Tiny\DI\Container|\Tiny\DI\ContainerInterface
     */
    public function __get($key)
    {
        switch ($key) {
            case 'cookie':
                return $this->application->get(HttpCookie::class);
            case 'session':
                return $this->application->get(HttpSession::class);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\Request\Request::initData()
     */
    protected function initData()
    {
        $this->param->merge($_REQUEST);
        $this->get = new Get($_GET);
        $this->post = new Post($_POST); 
        
        // SCRIPT
        $server = $this->server;
        $this->host = $server['HTTP_HOST'];
        $this->port = $server['SERVER_PORT'];
        $this->method = $server['REQUEST_METHOD'];
        $this->scriptName = $server['SCRIPT_NAME'];
        $this->uri = $server['REQUEST_URI'];
        $this->isHttps = (443 === $this->port);
        $this->isPost = ('POST' === $this->method);
        $this->userAgent = $server['HTTP_USER_AGENT'];
        $this->referer = $server['HTTP_REFERER'];
        $this->rootDir = $server['DOCUMENT_ROOT'];
        $this->pathinfo = $server['PATH_INFO'];
        $this->queryString = $server['QUERY_STRING'];
        
        // url
        $http = 443 == $this->port ? 'https://' : 'http://';
        $port = in_array($this->port, [80, 443]) ? '' : ':' . $this->port;
        $this->url =  $http . $this->host . $port . $this->uri;
        
        // ip
        $this->ip =  ($server['HTTP_X_FORWARDED_FOR']) ? $server['HTTP_X_FORWARDED_FOR'] : ($server['HTTP_CLIENT_IP'] ?? $server['REMOTE_ADDR']);

        //route
        $this->routeContext = $this->queryString ? substr($this->uri, 0, strpos($this->uri, '?')) : $this->uri;
    }
}
?>