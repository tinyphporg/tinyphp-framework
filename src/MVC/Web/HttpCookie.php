<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name HttpCookie.php
 * @author King
 * @version Beta 1.0
 * @Date: Mon Dec 19 00:14 52 CST 2011
 * @Description HttpCookie 操纵CooKie类
 * @Class List
 *        1.HttpCookie
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Mon Dec 19 00:14:52 CST 2011 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\Web;

use Tiny\Tiny;
use Tiny\MVC\Request\WebRequest;

/**
 * Cookie
 *
 * @package Tiny.MVC.Web
 * @since : Mon Dec 19 00:15 53 CST 2011
 * @final : Mon Dec 19 00:15 53 CST 2011
 */
class HttpCookie implements \ArrayAccess, \Iterator, \Countable
{

    /**
     * 单例模式
     *
     * @var HttpCookie
     */
    protected static $_instance;

    /**
     * cookie
     *
     * @var array
     */
    protected $_cookies = FALSE;

    /**
     * cookie域名
     *
     * @var string
     */
    protected $_domain = '';

    /**
     * 过期时间
     *
     * @var int
     */
    protected $_expires = 360000;

    /**
     * cookie前缀
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * cookie作用路径
     *
     * @var string
     */
    protected $_path = '/';

    /**
     * 是否编码
     *
     * @var bool
     */
    protected $_isEncode = FALSE;

    /**
     * 获取单一实例
     *
     * @param array $cookies 预设的cookies数据
     * @return self
     */
    public static function getInstance($policy =  [])
    {
        if (!self::$_instance)
        {
            self::$_instance = new self($policy);
        }
        return self::$_instance;
    }

    /**
     * 构造函数
     *
     * @param array $policy 策略配置数组
     * @return void
     */
    protected function __construct(array $policy)
    {
        $this->_cookies = $policy['data'];
        $this->_domain = $policy['domain'];
        $this->_expires = (int)$policy['expires'];
        $this->_prefix = (string)$policy['prefix'];
        $this->_path = $policy['path'];
        $this->_isEncode = (bool)$policy['encode'];
    }

    /**
     * 获取 COOKIE 数据
     *
     * @param string $name
     *        域名称,如果为空则返回整个 $COOKIE 数组
     * @param boolean $decode
     *        是否自动解密,如果 set() 时加密了则这里必需要解密,并且解密只能针对单个值
     * @return mixed
     */
    public function get($name = NULL)
    {
        $name = $this->_prefix . $name;
        $value = $name ? $this->_cookies[$name] : $this->_cookies;
        if ($this->_isEncode)
        {
            $value = $this->_decode($value);
        }
        return $value;
    }

    /**
     * 设置COOKIE
     *
     * @param string $name
     *        COOKIE名称
     * @param string $value
     *        值
     * @param int $time
     *        有效时间,以秒为单位 0 表示会话期间内
     * @param string $domain
     *        域名
     * @return bool
     */
    public function set($name, $value, $time = NULL, $path = NULL, $domain = NULL)
    {
        $name = $this->_prefix . $name;
        $path = $path ?: $this->_path;
        $domain = $domain ?: $this->_domain;
        $time = (int)$time ?: $this->_expires;
        $time = $time + time();
        if ($this->_isEncode)
        {
            $value = $this->_encode($value);
        }
        return setcookie($name, $value, $time, $path, $domain);
    }

    /**
     * 删除 COOKIE
     *
     * @param string $name
     *        COOKIE名称
     * @return void
     */
    public function remove($name)
    {
        $this->set($name, NULL, -86400 * 365);
    }

    /**
     * 清除 COOKIE
     *
     * @return void
     */
    public function clean()
    {
        foreach ($this->_cookies as $key => $val)
        {
            $this->remove($key);
        }
    }

    /**
     * 实现接口之获取
     *
     * @param string $key
     * @return void
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * 实现接口之设置
     *
     * @param string $key
     *        键
     * @param string $value
     *        值 其他值均为默认值
     * @return
     *
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * 实现接口之是否存在
     *
     *
     * @param string $key
     *        键
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->_cookies[$key]);
    }

    /**
     * 实现接口之移除cookie
     *
     * @param string $key
     *        cookie的键
     * @return bool
     */
    public function offsetUnset($key)
    {
        return $this->remove($key);
    }

    /**
     * Iterator rewind
     *
     * @return mixed
     */
    public function rewind()
    {
        return reset($this->_cookies);
    }

    /**
     * Iterator current
     *
     * @return mixed
     */
    public function current()
    {
        $current = current($this->_cookies);
        if ($this->_isEncode)
        {
            $current = $this->_decode($current);
        }
        return $current;
    }

    /**
     * Iterator next
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->_cookies);
    }

    /**
     * Iterator key
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_cookies);
    }

    /**
     * Iterator valid
     *
     * @return mixed
     *
     */
    public function valid()
    {
        return NULL !== key($this->_cookies);
    }

    /**
     * 输出字符
     *
     * @return string
     */
    public function __toString()
    {
        return var_export($this->_cookies, TRUE);
    }

    /**
     * 获取总计
     *
     * @return int
     */
    public function count()
    {
        return count($this->_cookies);
    }

    /**
     * 私有方法：加密 COOKIE 数据
     *
     * @param string $value 值
     * @return string
     */
    protected function _encode($value)
    {
        if (!is_array($value))
        {
            $value = base64_encode($value);
            $search = [
                '=',
                '+',
                '/'
            ];
            $replace = [
                '_',
                '-',
                '|'
            ];
            return str_replace($search, $replace, $value);
        }

        $data = [];
        foreach ($value as $key => $val)
        {
            $data[$key] = $this->_encode($val);
        }
        return $data;
    }

    /**
     * 私有方法：解密 COOKIE 数据
     *
     * @param string $value 值
     * @return string
     */
    protected function _decode($value)
    {
        if (!is_array($value))
        {
            $replace = [
                '=',
                '+',
                '/'
            ];
            $search = [
                '_',
                '-',
                '|'
            ];
            $str = str_replace($search, $replace, $value);
            return base64_decode($str);
        }
        $data = [];
        foreach ($value as $key => $val)
        {
            $data[$key] = $this->_decode($val);
        }
        return $data;
    }
}
