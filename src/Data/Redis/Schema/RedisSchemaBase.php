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
 *          King 2020年03月5日23:36:00 stable 1.0 审定稳定版本
 */
namespace Tiny\Data\Redis\Schema;

use Tiny\Data\Redis\RedisException;

/**
 * Redis的数据结构基类
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30上午05:20:21
 * @final 2013-11-30上午05:20:21
 */
abstract class RedisSchemaBase
{

    /**
     * 字符类型
     *
     * @var string
     */
    const SCHEMA_TYPE_STRING = \Redis::REDIS_STRING;

    /**
     * SET类型
     *
     * @var string
     */
    const SCHEMA_TYPE_SET = \Redis::REDIS_SET;

    /**
     * LIST类型
     *
     * @var string
     *
     */
    const SCHEMA_TYPE_LIST = \Redis::REDIS_LIST;

    /**
     * ZSET类型
     *
     * @var string
     */
    const SCHEMA_TYPE_ZSET = \Redis::REDIS_ZSET;

    /**
     * HASH类型
     *
     * @var string
     */
    const SCHEMA_TYPE_HASH = \Redis::REDIS_HASH;

    /**
     * 未知类型
     *
     * @var string
     */
    const SCHEMA_TYPE_NOT_FOUND = \Redis::REDIS_NOT_FOUND;
    
    /**
     * redis操作实例
     *
     * @var \Redis
     */
    protected $redis;

    /**
     * 操作键名称
     *
     * @var string
     */
    protected $key;

    /**
     * 默认的数据结构类型
     * 
     * @var int
     */
    protected $schemaType = self::SCHEMA_TYPE_NOT_FOUND;
    
    /**
     * 构造函数
     *
     * @param \Redis $redis
     *        redis连接实例
     * @param string $key
     *        redis的操作键名称
     * @return void
     */
    public function __construct(\RedisArray $redis, string $key)
    {
        $this->redis = $redis;
        $this->key = $key;
        if (!$key) {
            throw new RedisSchemaException(sprintf('Failed to create %s from redis: key is null', __CLASS__));
        }
        
        if ($this->exists() && $this->type() !== $this->schemaType) {
            throw new RedisSchemaException(sprintf('Failed to create %s from redis: type %s is not %s',$this->type(), $this->schemaType));
        }
    }

    /**
     * 删除
     *
     * @return bool
     */
    public function delete()
    {
        return $this->redis->delete($this->key);
    }

    /**
     * 是否存在该键
     *
     * @return bool
     */
    public function exists()
    {
        return $this->redis->exists($this->key);
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
        return $this->redis->expire($this->key, $time);
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
        return $this->redis->expireAt($this->key, $timeStamp);
    }

    /**
     * 返回存活时间
     *
     * @return int
     */
    public function ttl()
    {
        return $this->redis->ttl();
    }

    /**
     * 返回键值类型
     *
     * @return string
     */
    public function type()
    {
        return $this->redis->type($this->key);
    }
}
?>