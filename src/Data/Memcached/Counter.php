<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Counter.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-4上午09:43:50
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-12-4上午09:43:50 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Memcached;

/**
 * memcached实现的计数器
 *
 * @package Tiny.Data.Member
 * @since 2013-11-30下午01:51:35
 * @final 2013-11-30下午01:51:35
 */
class Counter
{
    
    /**
     * Memcached 数据源实例
     *
     * @var Memcached
     *
     */
    protected $memcached;
    
    /**
     * memcached的键
     *
     * @var string
     *
     */
    protected $key;
    
    /**
     * 构造函数
     *
     * @param Memcached memcached操作实例
     * @param string $key 键名
     */
    public function __construct(Memcached $memcached, string $key)
    {
        $this->memcached = $memcached;
        $this->key = $key;
    }
    
    /**
     * 获取key的值
     *
     * @return int
     */
    public function get()
    {
        return $this->memcached->get($this->key);
    }
    
    /**
     * 自增
     *
     * @param int $step 步进 默认为1
     * @return bool
     */
    public function incr(int $step = 1)
    {
        return $this->memcached->incr($this->key, $step);
    }
    
    /**
     * 自减
     *
     * @param int $step 步进 默认为1
     * @return bool
     */
    public function decr(int $step = 1)
    {
        return $this->memcached->decr($this->key, $step);
    }
    
    /**
     * 重置计数器
     *
     * @return bool
     */
    public function reset()
    {
        return $this->memcached->set($this->key, 0);
    }
    
    /**
     * 字符串化
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->get($this->key);
    }
}
?>