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
 *          King 2020年03月5日23:40:00 stable 1.0.01 审定稳定版本
 */
namespace Tiny\Data\Redis;

/**
 * 计数器
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30下午01:51:35
 * @final 2013-11-30下午01:51:35
 */
class Counter extends Base
{

    /**
     * 获取字符串的值
     *
     * @return int
     */
    public function get()
    {
        return $this->_redis->get($this->_key);
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
        if (1 == $step)
        {
            return $this->_redis->incr($this->_key);
        }
        return $this->_redis->incrBy($this->_key, $step);
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
        if (1 == $step)
        {
            return $this->_redis->decr($this->_key);
        }
        return $this->_redis->decrBy($this->_key, $step);
    }

    /**
     * 重置计数器
     *
     * @return bool
     */
    public function reset()
    {
        return $this->_redis->set($this->_key, 0);
    }

    /**
     * 字符串化
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->get($this->_key);
    }
}
?>