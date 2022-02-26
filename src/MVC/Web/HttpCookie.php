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
     * cookie
     *
     * @var array
     */
    protected $cookies;
    
    /**
     * cookie域名
     *
     * @var string
     */
    protected $domain;
    
    /**
     * 过期时间
     *
     * @var int
     */
    protected $expires = 360000;
    
    /**
     * cookie前缀
     *
     * @var string
     */
    protected $prefix;
    
    /**
     * cookie作用路径
     *
     * @var string
     */
    protected $path;
    
    /**
     * 是否编码
     *
     * @var bool
     */
    protected $isEncode;
    
    /**
     * 构造函数
     *
     * @param array $policy 策略配置数组
     * @return void
     */
    public function __construct(array $config)
    {
        $this->cookies = (array)$config['data'] ?: [];
        $this->domain = (string)$config['domain'] ?: '';
        $this->expires = (int)$config['expires'] ?: 36000;
        $this->prefix = (string)$config['prefix'] ?: '';
        $this->path = $config['path'] ?: '/';
        $this->isEncode = (bool)$config['encode'] ?: false;
    }
    
    /**
     * 获取 COOKIE 数据
     *
     * @param string $name 域名称,如果为空则返回整个 $COOKIE 数组
     * @param boolean $decode 是否自动解密,如果 set() 时加密了则这里必需要解密,并且解密只能针对单个值
     * @return mixed
     */
    public function get($name = null)
    {
        $name = $this->prefix . $name;
        $value = $name ? $this->cookies[$name] : $this->cookies;
        if ($this->isEncode) {
            $value = $this->decode($value);
        }
        return $value;
    }
    
    /**
     * 设置COOKIE
     *
     * @param string $name COOKIE名称
     * @param string $value 值
     * @param int $time 有效时间,以秒为单位 0 表示会话期间内
     * @param string $domain 域名
     * @return bool
     */
    public function set($name, $value, $time = null, $path = null, $domain = null)
    {
        $name = $this->prefix . $name;
        $path = $path ?: $this->path;
        $domain = $domain ?: $this->domain;
        $time = (int)$time ?: $this->expires;
        $time = $time + time();
        if ($this->isEncode) {
            $value = $this->encode($value);
        }
        return setcookie($name, $value, $time, $path, $domain);
    }
    
    /**
     * 删除 COOKIE
     *
     * @param string $name COOKIE名称
     * @return void
     */
    public function remove($name)
    {
        $this->set($name, null, -86400 * 365);
    }
    
    /**
     * 清除 COOKIE
     *
     * @return void
     */
    public function clean()
    {
        foreach ($this->cookies as $key => $val) {
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
     * @param string $key 键
     * @param string $value 值 其他值均为默认值
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
     * @param string $key 键
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->cookies[$key]);
    }
    
    /**
     * 实现接口之移除cookie
     *
     * @param string $key cookie的键
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
        return reset($this->cookies);
    }
    
    /**
     * Iterator current
     *
     * @return mixed
     */
    public function current()
    {
        $current = current($this->cookies);
        if ($this->isEncode) {
            $current = $this->decode($current);
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
        return next($this->cookies);
    }
    
    /**
     * Iterator key
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->cookies);
    }
    
    /**
     * Iterator valid
     *
     * @return mixed
     *
     */
    public function valid()
    {
        return null !== key($this->cookies);
    }
    
    /**
     * 输出字符
     *
     * @return string
     */
    public function __toString()
    {
        return var_export($this->cookies, true);
    }
    
    /**
     * 获取总计
     *
     * @return int
     */
    public function count()
    {
        return count($this->cookies);
    }
    
    /**
     * 私有方法：加密 COOKIE 数据
     *
     * @param string $value 值
     * @return string
     */
    protected function encode($value)
    {
        if (!is_array($value)) {
            $value = base64_encode($value);
            return str_replace([
                '=',
                '+',
                '/'
            ], [
                '_',
                '-',
                '|'
            ], $value);
        }
        
        $data = [];
        foreach ($value as $key => $val) {
            $data[$key] = $this->encode($val);
        }
        return $data;
    }
    
    /**
     * 私有方法：解密 COOKIE 数据
     *
     * @param string $value 值
     * @return string
     */
    protected function decode($value)
    {
        if (!is_array($value)) {
            $str = str_replace([
                '_',
                '-',
                '|'
            ], [
                '=',
                '+',
                '/'
            ], $value);
            return base64_decode($str);
        }
        $data = [];
        foreach ($value as $key => $val) {
            $data[$key] = $this->decode($val);
        }
        return $data;
    }
}
