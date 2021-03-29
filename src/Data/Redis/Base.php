<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-30上午04:28:01
 * @Description Redis的数据结构基类
 * @Class List
 *        1. Base redis操作数据结构的基类
 *
 * @History <author> <time> <version > <desc>
 *          king 2013-11-30上午04:28:01 1.0 第一次建立该文件
 *          King 2020年03月5日23:36:00 stable 1.0.01 审定稳定版本
 */
namespace Tiny\Data\Redis;

use Tiny\Data\RedisException;

/**
 * Redis的数据结构基类
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30上午05:20:21
 * @final 2013-11-30上午05:20:21
 */
abstract class Base
{

    /**
     * 字符类型
     *
     * @var string
     */
    const TYPE_STRING = \Redis::REDIS_STRING;

    /**
     * SET类型
     *
     * @var string
     */
    const TYPE_SET = \Redis::REDIS_SET;

    /**
     * LIST类型
     *
     * @var string
     *
     */
    const TYPE_LIST = \Redis::REDIS_LIST;

    /**
     * ZSET类型
     *
     * @var string
     */
    const TYPE_ZSET = \Redis::REDIS_ZSET;

    /**
     * HASH类型
     *
     * @var string
     */
    const TYPE_HASH = \Redis::REDIS_HASH;

    /**
     * 未知类型
     *
     * @var string
     */
    const TYPE_NOT_FOUND = \Redis::REDIS_NOT_FOUND;

    /**
     * redis操作实例
     *
     * @var \Redis
     */
    protected $_redis = NULL;

    /**
     * 操作键名称
     *
     * @var string
     */
    protected $_key = '';

    /**
     * 构造函数
     *
     * @param \Redis $redis
     *        redis连接实例
     * @param string $key
     *        redis的操作键名称
     * @return void
     */
    public function __construct($redis, $key)
    {
        $this->_redis = $redis;
        $this->_key = (string)$key;
        if (!$redis instanceof \Redis || !$redis instanceof \RedisArray)
        {
            throw new RedisException(sprintf('Failed to create %s from redis: the class is not an instance of  \Redis or \RedisArray', __CLASS__));
        }
        if ('' == $key)
        {
            throw new RedisException(sprintf('Failed to create %s from redis: key is null', __CLASS__));
        }
    }

    /**
     * 删除
     *
     * @return bool
     */
    public function delete()
    {
        return $this->_redis->delete($this->_key);
    }

    /**
     * 是否存在该键
     *
     * @return bool
     */
    public function exists()
    {
        return $this->_redis->exists($this->_key);
    }

    /**
     * 设置过期时间
     *
     * @param int $time
     *        过期秒数
     * @return bool
     */
    public function expire($time = 0)
    {
        return $this->_redis->expire($this->_key, $time);
    }

    /**
     * 设置一个过期的时间戳
     *
     * @param int $timeStamp
     *        时间戳
     * @return bool
     */
    public function expireAt($timeStamp)
    {
        return $this->_redis->expireAt($this->_key, $timeStamp);
    }

    /**
     * 返回存活时间
     *
     * @return int
     */
    public function ttl()
    {
        return $this->_redis->ttl();
    }

    /**
     * 返回键值类型
     *
     * @return string
     */
    public function type()
    {
        return $this->_redis->type($this->_key);
    }
}
?>