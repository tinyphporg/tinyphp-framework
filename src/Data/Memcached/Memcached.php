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

use Tiny\Data\IDataSchema;
use Tiny\Data\Db\Memcached\MemcachedException;

/**
 * memcached操作实例
 *
 * @package Tiny.Data.Memcached
 * @since 2013-12-1上午05:33:08
 * @final 2013-12-1上午05:33:08
 *        King 2020年03月5日15:48:00 stable 1.0 审定稳定版本
 */
class Memcached implements IDataSchema
{

    /**
     * memcache连接句柄
     *
     * @var \Memcached
     */
    protected $_connection;

    /**
     * 默认的服务器缓存策略
     *
     * @var array
     */
    protected $_policy = [
        'servers' => [[
            'host' => '127.0.0.1',
            'port' => 11211
        ]],                    /*缓存服务器设置*/
        'compressed' => TRUE,   /*是否压缩缓存数据*/
        'lifetime' => 0,       /*缓存生命周期*/
   		'poolname' => NULL,
        'pconnect' => TRUE,
        'memcached' => TRUE
    ];

    /**
     * 初始化构造函数
     *
     *
     * @param array $policy
     *        配置
     */
    function __construct(array $policy = [])
    {
        //检测服务组配置
        if (!is_array($policy['servers']) && isset($policy['host']))
        {
            $port = $policy['prot'] ?: 11211;
            $policy['servers'] = [['host' => $policy['host'], 'port' => $port]];
        }
        if (is_array($policy['servers']))
        {
            unset($this->_policy['servers']);
        }

        $policy = array_merge($this->_policy, $policy);
        $policy['compressed'] = (bool)$policy['compressed'];

        // 检测是否有memcached扩展
        $policy['memcached'] = (bool)$policy['memcached'];
        if (!extension_loaded('memcached'))
        {
            $policy['memcached'] = FALSE;
        }
        if (!$policy['memcached'] && !extension_loaded('memcache'))
        {
            throw new MemcachedException("extension.memcache or extension.memcached is not loaded!");
        }
        $this->_policy = $policy;
    }

    /**
     * 返回连接后的类
     *
     * @return \Memcached
     */
    public function getConnector()
    {
        if ($this->_connection)
        {
            return $this->_connection;
        }

        if (!is_array($this->_policy['servers']))
        {
            throw new MemcachedException('Data.Memcached connect failed: policy.servers 不是有效配置');
        }

        // 开始连接
        $this->_connection = $this->_policy['memcached'] ? $this->_connectMemcached($this->_policy) : $this->_connectMemcache($this->_policy);
        return $this->_connection;
    }

    /**
     * 关闭或者销毁实例和链接
     *
     * @return bool
     */
    public function close()
    {
        return $this->getConnector()->close();
    }

    /**
     * 写入缓存
     *
     * @param string $key
     * @param mixed $data
     * @param array $policy
     * @return boolean
     */
    public function set($key, $value = NULL, $life = NULL)
    {
        if (is_array($key))
        {
            $life = $value;
        }

        $conn = $this->getConnector();
        $life = is_null($life) ? $this->_policy['lifetime'] : $life;
        if ($this->_policy['memcached'])
        {
            return is_array($key) ? $conn->setMulti($key, $life) : $conn->set($key, $value, $life);
        }

        $compressed = $this->_policy['compressed'] ? \MEMCACHE_COMPRESSED : 0;
        if (!is_array($key))
        {
            return $conn->set($key, $value, $compressed, $life);
        }
        foreach ($key as $k => $v)
        {
            $conn->set($k, $v, $compressed, $life);
        }
        return TRUE;
    }

    /**
     * 读取缓存，失败或缓存撒失效时返回 false
     *
     * @param string $key
     *        键
     * @return mixed
     */
    public function get($key)
    {
        $conn = $this->getConnector();
        if ($this->_policy['memcached'])
        {
            return is_array($key) ? $conn->getMulti($key) : $conn->get($key);
        }
        if (!is_array($key))
        {
            $conn->get($key);
        }
        $rets = [];
        foreach ($key as $k)
        {
            $rets[$k] = $conn->get($k);
        }
        return $rets;
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
     * @param $key string
     *        其他需要求差集的键
     * @return int
     */
    public function incr($key, $step = 1)
    {
        return $this->getConnector()->increment($key, $step);
    }

    /**
     * 自减
     *
     * @param $key string
     *        其他需要求差集的键
     * @return int
     */
    public function decr($key, $step = 1)
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
     * @param string $method
     *        函数名称
     * @param array $params
     *        参数组
     * @return mixed
     */
    public function __call($method, $params)
    {
        $conn = $this->getConnector();
        return call_user_func_array([
            $conn,
            $method
        ], $params);
    }

    /**
     * 创建memcache连接
     *
     * @param array $policy
     * @return \Memcached
     */
    protected function _connectMemcached(array $policy)
    {
        $connection = new \Memcached($policy['poolname']);
        $connection->addServers($policy['servers']);
        $connection->setOption(\Memcached::OPT_COMPRESSION, $policy['compressed']);
        return $connection;
    }

    /**
     * 创建memcache连接
     *
     * @param array $policy
     * @return \Memcache
     */
    protected function _connectMemcache(array $policy)
    {
        $connection = new \Memcache();
        foreach ($policy['servers'] as $serv)
        {
            if (!$serv['weight'])
            {
                $serv['weigh'] = NULL;
            }
            $connection->addServer($serv['host'], $serv['port'], $policy['pconnect'], $serv['werght']);
        }
        return $connection;
    }
}
?>