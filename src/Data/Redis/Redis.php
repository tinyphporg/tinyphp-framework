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
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Redis;

use Tiny\Data\DataSourceInterface;
use Tiny\Data\Redis\Schema\Queue;
use Tiny\Data\Memcached\Counter;

/**
 * redis的操作类
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30上午02:36:20
 * @final 2013-11-30上午02:36:20
 */
class Redis implements DataSourceInterface
{
    
    /**
     * redis 连接操作类
     *
     * @var \RedisArray
     */
    protected $connector;
    
    /**
     * redis连接的servers
     *
     * @var array
     */
    protected $servers;
    
    /**
     * redis连接的选项数组
     *
     * @var array
     */
    protected $options = [
        'laze_connect' => true, // 惰性连接
    ];
    
    /**
     * 统一的构造函数
     *
     * @param array $policy 默认为空函数
     * @return void
     */
    public function __construct(array $config = [])
    {
        if (!$config) {
            return;
        }
        
        // servers
        $servers = (array)$config['servers'];
        if (isset($config['host'])) {
            $port = (int)$config['port'] ?: 6379;
            $servers[] = [
                'host' => $config['host'],
                'port' => $port
            ];
        }
        if (!$servers) {
            throw new RedisException('Initialization failure: DataSource.redis.servers must be an array!');
        }
        $this->servers = $this->formatServers($servers);
        
        // options
        $options = (array)$config['options'];
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
    }
    
    /**
     * 返回连接后的类或者句柄
     *
     * @return \Redis
     */
    public function getConnector()
    {
        if (!$this->connector) {
            $this->connector = new \RedisArray($this->servers, $this->options);
            
            // 是否启用IGB
            if (defined('\Redis::SERIALIZER_IGBINARY')) {
               $this->connector->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
            }
        }
        return $this->connector;
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
     * @param string $key 键名
     * @return Counter
     */
    public function createCounter($key)
    {
        return new Counter($this->getConnector(), $key);
    }
    
    /**
     * 创建一个队列对象
     *
     * @param string $key 键名
     * @return Queue
     */
    public function createQueue(string $key)
    {
        return new Queue($this->getConnector(), $key);
    }
    
    /**
     * 创建一个哈希表对象
     *
     * @param string $key 键名
     * @return HashTable
     */
    public function createHashTable(string $key)
    {
        return new HashTable($this->getConnector(), $key);
    }
    
    /**
     * 创建一个集合对象
     *
     * @param string $key 键名
     * @return Set
     */
    public function createSet(string $key)
    {
        return new Set($this->getConnector(), $key);
    }
    
    /**
     * 创建一个有序集合对象
     *
     * @param void
     * @return Set
     */
    public function createSortSet($key)
    {
        return new SortSet($this->getConnector(), $key);
    }
    
    /**
     * 调用连接实例的函数
     *
     * @param string $method 函数名称
     * @param array $params 参数组
     * @return mixed type
     */
    public function __call(string $method, array $params)
    {
        return call_user_func_array([
            $this->getConnector(),
            $method
        ], $params);
    }
    
    /**
     * 格式化servers
     *
     * @param array $servers
     * @return array
     */
    protected function formatServers(array $servers)
    {
        $toservers = [];
        foreach ($servers as $key => &$server) {
            if (is_array($server)) {
                if (isset($server['host'])) {
                    $toservers[] = $server['host'] . ':' . strval($server['port'] ?? 6379);
                    continue;
                }
            }
            if (is_string($server)) {
                $toservers = $server;
            }
        }
        return $toservers;
    }
}
?>