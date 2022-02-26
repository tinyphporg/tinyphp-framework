<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name RedisCache.php
 * @author King
 * @version Beta 1.0
 * @Date Fri Jan 20 13:02:18 CST 2012
 * @Description Redis缓存实例
 * @Class List
 *        1.Redis redis缓存类
 * @History <author> <time> <version > <desc>
 *          King Fri Jan 20 13:02:18 CST 2012 Beta 1.0 第一次建立该文件
 *          King 2020年02月24日19:49:00 stable 1.0 审定稳定版本
 *
 */
namespace Tiny\Cache\Storager;

/**
 * Redis缓存类
 *
 * @package Tiny.Cache
 * @since Sat Jan 21 17:32:07 CST 2012
 * @final Sat Jan 21 17:32:07 CST 2012
 *        King 2020年02月24日19:49:00 stable 1.0 审定稳定版本
 */
use Tiny\Data\Redis\Redis as RedisHandler;
use Tiny\Tiny;
use Tiny\Data\Data;
use Tiny\Cache\CacheInterface;
use Tiny\Cache\CacheException;

/**
 * Redis缓存操作类
 *
 * @package Tiny.Cache
 * @since 2013-12-1下午03:32:18
 * @final 2013-12-1下午03:32:18
 */
class Redis extends CacheStorager
{
    
    /**
     * redis连接句柄
     *
     * @var \Redis
     */
    protected $redis;
    
    /**
     * 默认缓存过期时间
     *
     * @var integer
     */
    protected $ttl = 60;
    
    /**
     * Data 的数据源ID
     *
     * @var string
     */
    protected $dataId;
    
    /**
     * 初始化构造函数
     *
     * @param array $policy 代理数组
     * @return void
     *
     */
    function __construct(array $config = [])
    {
        // tiny.data
        $dataId = (string)$config['dataid'];
        if (!$dataId) {
            throw new CacheException(sprintf('Class %s instantiation failed:  cache.config.dataid is not set!', self::class));
        }
        $this->dataId = $dataId;
        
        // ttl
        $ttl = (int)$config['ttl'];
        if ($ttl > 0) {
            $this->ttl = $ttl;
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = null)
    {
        $result = $this->getRedis()->get($key);
        return $result === false ? $default : $result;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::getMultiple()
     */
    public function getMultiple(array $keys, $default = null)
    {
        $results = $this->getRedis()->mget($keys);
        foreach ($results as &$result) {
            if (!$result) {
                $result = $default;
            }
        }
        return $results;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::set()
     */
    public function set(string $key, $value, int $ttl = 0)
    {
        if (!$ttl) {
            $ttl = $this->ttl;
        }
        return $this->getRedis()->setex($key, $ttl, $value);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::setMultiple()
     */
    public function setMultiple(array $values, int $ttl = 0)
    {
        if (!$ttl) {
            $ttl = $this->ttl;
        }
        
        $redis = $this->getRedis();
        $redis->multi(\Redis::PIPELINE);
        foreach ($values as $key => $value) {
            $redis->setex($key, $ttl, $value);
        }
        return $redis->exec();
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has(string $key)
    {
        return $this->getRedis()->exists($key);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete(string $key)
    {
        return $this->getRedis()->delete($key);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::clear()
     */
    public function clear()
    {
        return $this->getRedis()->flushDB();
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys)
    {
        $redis = $this->getRedis();
        $redis->multi(\Redis::PIPELINE);
        foreach ($keys as $key) {
            $redis->delete($key);
        }
        return $redis->exec();
    }
    
    /**
     * 获取链接
     *
     * @return \RedisArray
     */
    protected function getRedis()
    {
        if (!$this->redis) {
            $dataPool = Tiny::getApplication()->getData();
            $this->redis = $dataPool[$this->dataId];
            if (!$this->redis instanceof RedisHandler) {
                throw new CacheException(sprintf("Class %s is not an instance of %s!", get_class($this->redis), RedisHandler::class));
            }
        }
        return $this->redis->getConnector();
    }
}
?>