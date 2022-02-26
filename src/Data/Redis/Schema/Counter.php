<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Counter.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-30下午01:51:04
 * @Description Redis计数器
 * @Class List
 *        1. Counter Redis计数器
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-30下午01:51:04 1.0 第一次建立该文件
 *          King 2020年03月5日23:40:00 stable 1.0 审定稳定版本
 */
namespace Tiny\Data\Redis\Schema;

/**
 * 计数器
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30下午01:51:35
 * @final 2013-11-30下午01:51:35
 */
class Counter extends RedisSchemaBase
{
    /**
     * 当前数据结构的redis数据类型
     * 
     * @var int
     */
    protected $schemaType = \Redis::REDIS_STRING;
    
    /**
     * 获取字符串的值
     *
     * @return int
     */
    public function get()
    {
        return $this->redis->get($this->key);
    }

    /**
     * 自增
     *
     * @param int $step
     *        步进 默认为1
     * @return bool
     */
    public function incr(int $step = 1)
    {
        if (1 === $step)
        {
            return $this->redis->incr($this->key);
        }
        return $this->redis->incrBy($this->key, $step);
    }

    /**
     * 自减
     *
     * @param int $step
     *        步进 默认为1
     * @return bool
     */
    public function decr(int $step = 1)
    {
        if (1 === $step)
        {
            return $this->redis->decr($this->key);
        }
        return $this->redis->decrBy($this->key, $step);
    }

    /**
     * 重置计数器
     *
     * @return bool
     */
    public function reset()
    {
        return $this->redis->set($this->key, 0);
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