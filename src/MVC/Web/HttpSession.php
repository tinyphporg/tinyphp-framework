<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Session.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-3上午02:37:46
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-12-3上午02:37:46 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Web;

use Tiny\MVC\Web\Session\Redis;
use Tiny\MVC\Web\Session\Memcached;
use Tiny\MVC\Web\Session\SessionAdapterInterface;
use Tiny\MVC\Web\Session\SessionException;

/**
 * 服务器临时变量实例
 *
 * @package Web
 * @since Sun Dec 18 22:53 40 CST 2011
 * @final Sun Dec 18 22:53 40 CST 2011
 */
class HttpSession implements \ArrayAccess, \Iterator,\Countable, SessionAdapterInterface
{
    
    /**
     * session驱动数组
     *
     * @var array
     */
    protected static $sessionAdapterMap = [
        'redis' => Redis::class,
        'memcached' => Memcached::class
    ];
    
    /**
     * Session配置
     *
     * @var array
     */
    protected $config = [];
    
    /**
     * session
     *
     * @var SessionAdapterInterface
     */
    protected $session;
    
    /**
     * 注册session驱动类
     *
     * @param string $id 驱动ID
     * @param string $className 类名
     */
    public static function registerSessionAdpater($sessionId, $sessionClass)
    {
        if (key_exists($sessionId, self::$sessionAdapterMap)) {
            throw new SessionException('Failed to register the session adapter %s into the map: session id already exists!', $sessionClass);
        }
        self::$sessionAdapterMap[$sessionId] = $sessionClass;
    }
    
    /**
     * 获取session值
     *
     * @param string $key session KEY
     * @return string
     */
    public function get($key)
    {
        return $_SESSION[$key];
    }
    
    /**
     * 设置session值
     *
     * @param string $key
     * @param $value mixed 值
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * 移除session值
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * 获取Cooike中的Session
     *
     * @return string
     */
    public function getSessionName()
    {
        return session_name();
    }
    
    /**
     * 获取cookie中的SessionId
     *
     * @return string
     */
    public function getSessionId()
    {
        return session_id();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Iterator::rewind()
     */
    public function rewind()
    {
        return reset($_SESSION);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Iterator::current()
     */
    public function current()
    {
        return current($_SESSION);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Iterator::next()
     */
    public function next()
    {
        return next($_SESSION);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Iterator::key()
     */
    public function key()
    {
        return key($_SESSION);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Iterator::valid()
     */
    public function valid()
    {
        return key($_SESSION) !== null;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Countable::count()
     */
    public function count()
    {
        return count($_SESSION);
    }
    
    /**
     * 设置Session句柄
     *
     * @param string $id 句柄ID标示
     * @return bool
     */
    public function __construct(array $config = [])
    {
        if ($config['enabled']) {
            
            // session cookie params
            $domain = (string)$config['domain'] ?: '';
            $expires = intval($config['expires']);
            $path = $config['path'] ?: '/';
            session_set_cookie_params($expires, $path, $domain);
            
            // adpater
            $adapter = (string)$config['adapter'];
            if (!$config['adapter']) {
                throw new SessionException('Initialization failed, profile.session.adapter is required!');
            }
            if (!key_exists($adapter, self::$sessionAdapterMap)) {
                throw new SessionException(sprintf("Initialization failed, %s is not registered ", $adapter));
            }
            $config['class'] = self::$sessionAdapterMap[$adapter];
            $this->config = $config;
            session_set_save_handler($this, true);
        }
        session_start();
       
    }
    
    /**
     *
     * @param string $domain 域名
     *
     */
    public function setDomain($domain)
    {
        $this->getSession()->setDomain($domain);
    }
    
    /**
     * 打开Session
     *
     * @param string $savePath 保存路径
     * @param string $sessionName session名称
     */
    public function open($savePath, $sessionName)
    {
        return $this->getSession()->open($savePath, $sessionName);
    }
    
    /**
     * 关闭Session
     */
    public function close()
    {
        return $this->getSession()->close();
    }
    
    /**
     * 读Session
     *
     * @param string $sessionId Session身份标示
     * @return string
     */
    public function read($sessionId)
    {
        return $this->getSession()->read($sessionId) ?: '';
    }
    
    /**
     * 写Session
     *
     * @param string $sessionId SessionID标示
     * @param string $sessionValue Session值
     * @return bool
     */
    public function write($sessionId, $sessionValue)
    {
        return $this->getSession()->write($sessionId, $sessionValue);
    }
    
    /**
     * 注销某个变量
     *
     * @param string $sessionId Session身份标示
     * @return bool
     */
    public function destroy($sessionId)
    {
        return $this->getSession()->destroy($sessionId);
    }
    
    /**
     * 自动回收过期变量
     *
     * @param int $maxlifetime 最大生存时间
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return $this->getSession()->gc($maxlifetime);
    }
    
    /**
     * 实现ArrayAccess接口
     *
     * @param string $key 键名
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $_SESSION[$key];
    }
    
    /**
     * 实现ArrayAccess接口
     *
     * @param string $key 键名
     * @param mixed $value 值
     */
    public function offsetSet($key, $value)
    {
        $_SESSION[$key] = $value;
    }
    
    /**
     * 实现ArrayAccess接口
     *
     * @param string $key 键名
     * @return bool
     */
    public function offsetExists($key)
    {
        return (bool)$_SESSION[$key];
    }
    
    /**
     * 实现ArrayAccess接口
     *
     * @param string $key 键名
     */
    public function offsetUnset($key)
    {
        unset($_SESSION[$key]);
    }
    
    /**
     * 获取实例
     *
     * @return SessionAdapterInterface
     */
    protected function getSession()
    {
        if (!$this->session) {
            $config = [
                'expires' => $this->config['expires'],
                'dataid' => $this->config['dataid']
            ];
            $sessionClass = $this->config['class'];
            $this->session = new $sessionClass($config);
            if (!$this->session instanceof SessionAdapterInterface) {
                throw new SessionException(sprintf('Failed to instantiate class %s: does not implement %s', $sessionClass, SessionAdapterInterface::class));
            }
        }
        return $this->session;
    }
}
?>