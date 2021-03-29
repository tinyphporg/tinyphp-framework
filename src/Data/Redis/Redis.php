<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Schema.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-30上午02:35:09
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-30上午02:35:09 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Data\Redis;

use Tiny\Data\IDataSchema;

/**
 * redis的操作类
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30上午02:36:20
 * @final 2013-11-30上午02:36:20
 */
class Redis implements IDataSchema
{

    /**
     * redis连接实例
     *
     * @var \Redis
     */
    protected $_connection;

    /**
     * 默认的服务器缓存策略
     *
     * @var array
     * @access protected
     */
    protected $_policy = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'lifetime' => 3600,  /*缓存生命周期*/
        'persistent' => TRUE, /*是否使用持久链接*/
        'options' => NULL,    /*设置选项*/
        'auth' => NULL,
        'servers' => NULL
    ];

    /**
     * 统一的构造函数
     *
     * @param array $policy
     *        默认为空函数
     * @return void
     */
    public function __construct(array $policy = [])
    {
        $this->_policy = array_merge($this->_policy, $policy);
    }

    /**
     * 返回连接后的类或者句柄
     *
     * @return \Redis
     */
    public function getConnector()
    {

        if (!$this->_connection)
        {
            $connection = is_array($this->_policy['servers']) ? $this->_connectRedisArray($this->_policy) : $this->_connectRedis($this->_policy);
            $this->_connection = $connection;

            $options = is_array($this->_policy['options']) ? $this->_policy['options'] : [];
            // 是否启用IGB
            if (defined('\Redis::SERIALIZER_IGBINARY'))
            {
                $options[\Redis::OPT_SERIALIZER] = \Redis::SERIALIZER_IGBINARY;
            }
            foreach ($options as $k => $v)
            {
                $connection->setOption($k, $v);
            }
        }
        return $this->_connection;
    }

    /**
     * 关闭或者销毁实例和链接
     *
     * @return void
     */
    public function close()
    {
        return $this->getConnector()->close();
    }

    /**
     * 获取计数器实例
     *
     * @param string $key
     *        键名
     * @return Counter
     */
    public function createCounter($key)
    {
        return new Counter($this->getConnector(), $key);
    }

    /**
     * 创建一个队列对象
     *
     * @param string $key
     *        键名
     * @return Queue
     */
    public function createQueue(string $key)
    {
        return new Queue($this->getConnector(), $key);
    }

    /**
     * 创建一个哈希表对象
     *
     * @param string $key
     *        键名
     * @return HashTable
     */
    public function createHashTable(string $key)
    {
        return new HashTable($this->getConnector(), $key);
    }

    /**
     * 创建一个集合对象
     *
     * @param string $key
     *        键名
     * @return Set
     */
    public function createSet(string $key)
    {
        return new Set($this->getConnector(), $key);
    }

    /**
     * 创建一个有序集合对象
     *
     * @param
     *        void
     * @return Set
     */
    public function createSortSet($key)
    {
        return new SortSet($this->connect(), $key);
    }

    /**
     * 调用连接实例的函数
     *
     * @param string $method
     *        函数名称
     * @param array $params
     *        参数组
     * @return mixed type
     */
    public function __call($method, $params)
    {
        $connection = $this->getConnector();
        return call_user_func_array([
            $connection,
            $method
        ], $params);
    }

    /**
     * 连接多个redis连接池
     *
     * @param array $policy
     * @return \RedisArray
     */
    protected function _connectRedisArray(array $policy)
    {
        $hosts = [];
        $options = [];
        foreach ($policy['servers'] as $key => $serv)
        {
            if ($key === 'options')
            {
                echo $key;
                if (is_array($serv))
                {
                    $options = $serv;
                }
                continue;
            }
            $hosts[] = $serv['host'] . ':' . $serv['port'];
        }
        $connection = new \RedisArray($hosts, $options);
        return $connection;
    }

    /**
     * 连接单个redis
     *
     * @param array $policy
     * @return \Redis
     */
    protected function _connectRedis(array $policy)
    {
        $connection = new \Redis();
        $policy['port'] = (int)$policy['port'] ?: 6379;
        $policy['db'] = (int)$policy['db'];
        if ($policy['persistent'])
        {
            $connection->pconnect($policy['host'], $policy['port'], $policy['lifetime']);
        }
        else
        {
            $connection->connect($policy['host'], $policy['port'], $policy['lifetime']);
        }

        if ($policy['auth'])
        {
            $connection->auth($policy['auth']);
        }
        $connection->select($policy['db']);
        return $connection;
    }
}
?>