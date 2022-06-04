<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name CacheStorager.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午1:34:58
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午1:34:58 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Cache\Storager;

use Tiny\Cache\CacheInterface;

/**
 * 缓存持久类
 *
 * @package Tiny.Cache.Storager
 * @since 2022年2月12日下午1:35:55
 * @final 2022年2月12日下午1:35:55
 */
abstract class CacheStorager implements \ArrayAccess, CacheInterface
{ 
    /**
     * 初始化配置
     *
     * @param array $config 缓存存储器的配置数组
     * @return void
     *
     */
    abstract public function __construct(array $config = []);
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }
}

?>