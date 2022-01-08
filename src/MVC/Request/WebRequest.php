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

use Tiny\MVC\Request\Param\Readonly;
use Tiny\MVC\ApplicationBase;

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
     * 请求参数
     *
     * @var array
     */
    protected $_data;

    /**
     * 服务器参数
     *
     * @var array
     */
    protected $_server;

    /**
     * 获取路由字符串
     *
     * @return string
     */
    public function getRouterString()
    {
        return $this->uri;
    }

    /**
     * 设置路由参数
     *
     * @param array $param
     *        路由参数
     * @return void
     */
    public function setRouterParam(array $param)
    {
        $this->_data['get'] = array_merge($this->_data['get'], $param);
        $this->_data['request'] = array_merge($this->_data['request'], $param);
    }

    /**
     * 设置当前应用实例
     *
     * @param ApplicationBase $app
     * @return void
     */
    public function setApplication(ApplicationBase $app)
    {
        parent::setApplication($app);
    }
    
    /**
     * 获取request的初始化数值
     * 
     * @return array|[]
     */
    public function getRequestData()
    {
        return $this->_data;
    }
    
    /**
     * 魔术函数获取变量的值
     *
     * @param string $key
     *        成员变量名
     * @return mixed
     */
    protected function _magicGet($key)
    {
        switch (strtolower($key))
        {
            case 'get':
                return new Readonly($this->_data['get']);
            case 'post':
                return new Readonly($this->_data['post']);
            case 'param':
                return new Readonly($this->_data['request']);
            case 'server':
                return new Readonly($this->_server);
            case 'cookie':
                return $this->_app->getCookie();
            case 'session':
                return $this->_app->getSession();
            // case 'file':
            // return $this->_app->getFile();
            case 'files':
                return $this->_data['files'];
            case 'scriptname':
                return $this->_server['SCRIPT_NAME'];
            case 'ip':
                return $this->_getIp($this->_server);
            case 'url':
                return $this->_getUrl($this->_server);
            case 'uri':
                return $this->_server['REQUEST_URI'];
            case 'ishttps':
                return (443 == $this->_server['SERVER_PORT']);
            case 'port':
                return $this->_server['SERVER_PORT'];
            case 'pathinfo':
                return $this->_server['PATH_INFO'];
            case 'ispost':
                return 'POST' == $this->_server['REQUEST_METHOD'];
            case 'useragent':
                return $this->_server['HTTP_USER_AGENT'];
            case 'root':
                return $this->_server['DOCUMENT_ROOT'];
            case 'referer':
                return $this->_server['HTTP_REFERER'];
            case 'host':
                return $this->_server['HTTP_HOST'];
            default:
                return NULL;
        }
    }

    /**
     * 构造函数,初始化
     *
     * @return void
     */
    public function __construct()
    {
        $this->_data = [
            'cookie' => $_COOKIE,
            'request' => $_REQUEST,
            'post' => $_POST,
            'get' => $_GET,
            'files' => $_FILES,
            'server' => $_SERVER,
            'session'=> $_SESSION
        ];
        $this->_server = $_SERVER;
        $sessionName = ini_get('session.name');
        $sessionId = $_COOKIE[$sessionName];
        unset($_SERVER, $_REQUEST, $_COOKIE, $_POST, $_GET, $_FILES);
        if (isset($sessionId))
        {
            $_COOKIE[$sessionName] = $sessionId;
        }
    }

    /**
     * 获取客户端IP
     *
     * @param array $server
     *        服务端变量
     * @return string $clientIp
     */
    protected function _getIp(array $server)
    {
        if ($server['HTTP_X_FORWARDED_FOR'])
        {
            return $server['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($server['HTTP_CLIENT_IP']))
        {
            return $server['HTTP_CLIENT_IP'];
        }
        return $server['REMOTE_ADDR'];
    }

    /**
     * 获取完整URL
     *
     * @param array $server
     *        服务端变量
     * @return string
     */
    protected function _getUrl(array $server)
    {
        $http = 443 == $server['SERVER_PORT'] ? 'https://' : 'http://';
        $port = (443 == $server['SERVER_PORT'] || 80 == $server['SERVER_PORT']) ? '' : ':' . $server['SERVER_PORT'];
        return $http . $server['HTTP_HOST'] . $port . $server["REQUEST_URI"];
    }
}
?>