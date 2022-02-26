<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name memcached.php
 * @author King
 * @version Beta 1.0
 * @Date: Fri Dec 16 22 48 00 CST 2011
 * @Description
 * @Class List
 *        1.
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Fri Dec 16 22:48:00 CST 2011 Beta 1.0 第一次建立该文件
 *          King 2020年02月24日17:09:00 stable 1.0 审定稳定版本
 */
namespace Tiny\Cache\Storager;

use Tiny\Tiny;
use Tiny\Data\Memcached\Memcached as MemcachedHandler;
use Tiny\Cache\CacheException;
use Tiny\Data\Data;

/**
 * Memcache缓存
 *
 * @package Tiny.Cache
 * @since Fri Dec 16 22 48 07 CST 2011
 * @final Fri Dec 16 22 48 07 CST 2011
 *        King 2020年02月24日17:09:00 stable 1.0 审定稳定版本
 */
class Memcached extends CacheStorager
{
    
    /**
     * 默认生命周期
     *
     * @var integer
     */
    protected $ttl = 3600;
    
    /**
     * dacheId
     *
     * @var string
     */
    protected $dataId;
    
    /**
     * memcached操作实例
     *
     * @var Memcached
     */
    protected $memcached;
    
    /**
     * 初始化构造函数
     *
     * @param array $policy 代理数组
     * @return void
     */
    public function __construct(array $config = [])
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
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = null)
    {
        return $this->getMemcached()->get($key) ?: $default;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::getMultiple()
     */
    public function getMultiple(array $keys, $default = null) 
    {
        $results = [];
        foreach($keys as $key) {
            $results[$key] = $this->get($key, $default);    
        }
        return $results;
    }
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 缓存的值 $key为array时 为设置生命周期的值
     * @param int $life 缓存的生命周期
     * @return bool
     */
    public function set(string $key, $value, int $ttl = 0)
    {
        if (!$ttl) {
            $ttl = $this->ttl;
        }
        if ($ttl < 0) {
            return $this->delete($key);
        }
        return $this->getMemcached()->set($key, $value, $ttl);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::setMultiple()
     */
    public function setMultiple(array $values, int $ttl = 0)
    {
        $results = [];
        foreach($values as $key => $value) {
            $results[$key] = $this->set($key, $value, $ttl);
        }
        return $results;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has(string $key) {
        return $this->getMemcached()->get($key) !== false;    
    }
    
    
    /**
     *  
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete(string $key)
    {
        return $this->getMemcached()->delete($key);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple($keys) 
    {
        $results = [];
        foreach($keys as $key) {
            $results[$key] = $this->delete($key);
        }
        return $results;
    }
    
    /**
     * 清除所有缓存
     *
     * @param void
     * @return bool
     */
    public function clear()
    {
        return $this->getMemcached()->flush();
    }
    
    /**
     * 获取memcached操作实例
     *
     * @return MemcachedHandler
     */
    protected function getMemcached()
    {
        if (!$this->memcached) {
            $dataPool = Tiny::getApplication()->getData();
            $this->memcached = $dataPool[$this->dataId];
            if (!$this->memcached instanceof MemcachedHandler) {
                throw new CacheException(sprintf("Class %s is not an instance of %s!", get_class($this->memcached), MemcachedHandler::class));
            }
        }
        return $this->memcached;
    }
}