<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Schema.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-1上午05:32:31
 * @Description
 * @Class List
 *        1.Memcached memcached扩展
 * @History <author> <time> <version > <desc>
 *          king 2013-12-1上午05:32:31 1.0 第一次建立该文件
 *          King 2020年03月5日15:48:00 stable 1.0 审定稳定版本
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Memcached;

use Tiny\Data\Db\Memcached\MemcachedException;
use Tiny\Data\DataSourceInterface;

/**
 * memcached操作实例
 *
 * @package Tiny.Data.Memcached
 * @since 2013-12-1上午05:33:08
 * @final 2013-12-1上午05:33:08
 *        King 2020年03月5日15:48:00 stable 1.0 审定稳定版本
 */
class Memcached implements DataSourceInterface
{
    
    /**
     * memcached的数据源连接器
     *
     * @var \Memcached
     */
    protected $connector;
    
    /**
     * 过期时间
     *
     * @var integer
     */
    protected $ttl = 60;
    
    /**
     * 共享的持久化连接ID
     *
     * @var string
     */
    protected $persistentId;
    
    /**
     * 服务器池配置
     *
     * @var array
     */
    protected $servers = [
        [
            'host' => '127.0.0.1',
            'port' => 11211
        ]
    ];
    
    /**
     * 选型配置数组
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * 初始化构造函数
     *
     *
     * @param array $policy 配置
     */
    function __construct(array $config = [])
    {
        // 检测是否有memcached扩展
        if (!extension_loaded('memcached')) {
            throw new MemcachedException("Initialization failure: the PHP extension named Memcache is not installed");
        }
        
        // servers
        $servers = [];
        if (isset($config['host'])) {
            $port = $config['port'] ?: 11211;
            $servers[] = [
                'host' => $config['host'],
                'port' => $port
            ];
            unset($config['host'], $config['port']);
        } else 
            if (is_array($config['servers'])) {
                $servers = $config['servers'];
                unset($config['servers']);
            }
        if ($servers) {
            $this->servers = $servers;
        }
        if (!is_array($this->servers)) {
            throw new MemcachedException('Initialization failure:  config.servers is an invalid configuration');
        }
        //DataSource.Memcached connection failed:
        // ttl
        $ttl = (int)$config['ttl'];
        if ($ttl) {
            $this->ttl = $ttl;
        }
        
        // options
        $this->options = (array)$config['options'];
        
        // persistent id
        $this->persistentId = (string)$config['persistent_id'];
    }
    
    /**
     * 返回连接后的类
     *
     * @return \Memcached
     */
    public function getConnector()
    {
        if (!$this->connector) {
            $this->connector = new \Memcached($this->persistentId);
            $this->connector->addServers($this->servers);
            $this->connector->setOptions($this->options);
        }
        return $this->connector;
    }
    
    /**
     * 写入缓存
     *
     * @param string $key
     * @param mixed $data
     * @param array $policy
     * @return boolean
     */
    public function set(string $key, $value, int $expiration = 0)
    {
        if ($expiration == 0) {
            $expiration = $this->ttl;
        }
        if ($expiration < 0) {
            return $this->delete($key);
        }
        return $this->getConnector()->set($key, $value, $expiration);
    }
    
    /**
     *
     * @param array $values
     * @param int $expiration
     * @return array|boolean
     */
    public function setMuliple(array $values, int $expiration = null)
    {
        if ($expiration === null) {
            $expiration = $this->ttl;
        }
        if ($expiration < 0) {
            return $this->getConnector()->deleteMulti(array_keys($values));
        }
        return $this->getConnector()->setMulti($values, $expiration);
    }
    
    /**
     * 获取值
     *
     * @param string $key
     * @param mixed $default
     * @return string|mixed
     */
    public function get(string $key, $default = null)
    {
        $value = $this->getConnector()->get($key);
        if ($this->getConnector()->getResultCode() === \Memcached::RES_NOTFOUND) {
            return $default;
        }
        return $value;
    }
    
    /**
     * 批量取值
     *
     * @param array $keys
     * @return mixed
     */
    public function getMulti(array $keys)
    {
        return $this->getConnector()->getMulti($keys);
    }
    
    /**
     * 删除指定的缓存
     *
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key, int $time = 0)
    {
        return $this->getConnector()->delete($key, $time);
    }
    
    /**
     * 刷新所有的缓存数据
     *
     * @access ： public
     * @return bool
     */
    public function flush()
    {
        return $this->getConnector()->flush();
    }
    
    /**
     * 自增
     *
     *
     * @param $key string 其他需要求差集的键
     * @return int
     */
    public function incr(string $key, int $step = 1)
    {
        return $this->getConnector()->increment($key, $step);
    }
    
    /**
     * 自减
     *
     * @param $key string 其他需要求差集的键
     * @return int
     */
    public function decr(string $key, int $step = 1)
    {
        return $this->getConnector()->decrement($key, $step);
    }
    
    /**
     * 创建一个计时器实例
     *
     * @param string $key
     * @return \Tiny\Data\Memcached\Counter
     */
    public function createCounter(string $key)
    {
        return new Counter($this, $key);
    }
    
    /**
     * 获取服务端版本号
     *
     * @return string
     */
    public function version()
    {
        return $this->getConnector()->getVersion();
    }
    
    /**
     * 返回一个包含所有可用memcache服务器状态的数组
     *
     * @return array
     */
    public function stats()
    {
        return $this->getConnector()->getStats();
    }
    
    /**
     * 调用连接实例的函数
     *
     * @param string $method 函数名称
     * @param array $params 参数组
     * @return mixed
     */
    public function __call(string $method, array $params)
    {
        return call_user_func_array([
            $this->getConnector(),
            $method
        ], $params);
    }
}
?>