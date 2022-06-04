<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ApplicationCache.php
 * @author King
 * @version stable 2.0
 * @Date 2022年5月20日下午7:38:44
 * @Class List class
 * @Function List function_container
 * @History King 2022年5月20日下午7:38:44 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Application;

use Tiny\Cache\CacheInterface;
use Tiny\Cache\Storager\CacheStorager;
use Tiny\Cache\Storager\SingleCache;

/**
* 应用缓存
*  
* @package namespace
* @since 2022年5月20日下午7:48:24
* @final 2022年5月20日下午7:48:24
*/
class ApplicationCache implements CacheInterface
{
    /**
     * 缓存存储器shi'li
     * @var SingleCache
     */
    protected $cacheStorager;
    
    /**
     *
     * @var array
     */
    protected $data = [];
    
    /**
     * 是否更新过缓存
     *
     * @var boolean
     */
    protected $isUpdated = false;
    
    /**
     *  
     * @param CacheStorager $cacheStorager
     */
    public function __construct(?SingleCache $cacheStorager)
    {
        if ($cacheStorager) {
            $this->cacheStorager = $cacheStorager;
            $this->data = (array)$cacheStorager->getMultiple([]);
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has($key)
    {
        return key_exists($key, $this->data);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = NULL)
    {
        if (key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return [];
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::getMultiple()
     */
    public function getMultiple(array $keys, $default = NULL)
    {
        $res = [];
        foreach ($keys as $key) {
            if (key_exists($key, $this->data)) {
                $res[$key] = $this->data[$key];
            }
        }
        return $res;
    }
    
    /**
     * ttl无效
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::set()
     */
    public function set($key, $value, int $ttl = 0)
    {
        $this->isUpdated = true;
        $this->data[$key] = $value;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::setMultiple()
     */
    public function setMultiple(array $values, int $ttl = 0)
    {
        $this->isUpdated = true;
        foreach ((array)$values as $key => $value) {
            $this->data[$key] = $value;
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::clear()
     */
    public function clear()
    {
        $this->data = [];
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete(string $key)
    {
        if (key_exists($key, $this->data)) {
            unset($this->data[$key]);
            $this->isUpdated = true;
            return true;
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            if (key_exists($key, $this->data)) {
                unset($this->data[$key]);
                $this->isUpdated  = true;
            }
        }
    }
    
    /**
     * 析构函数自动保存
     */
    public function __destruct()
    {
        if (!$this->isUpdated || !$this->cacheStorager) {
            return;
        }
        $this->cacheStorager->setMultiple($this->data);
    }
}
?>