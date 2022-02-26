<?php
/**
 * @Copyright (C), 2013-, King.
 * @Name HashTable.php
 * @Author King
 * @Version 1.0
 * @Date: 2013-11-30上午04:27:26
 * @Description
 * @Class List
 *            1. HashTable 哈希表
 * @History <author> <time> <version > <desc>
               king 2013-11-30上午04:27:26  1.0  第一次建立该文件
               King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Redis;

use Tiny\Data\Redis\Schema\RedisSchemaBase;
use Tiny\Data\Redis\Schema\Hashtable\HashTableCounter;

/**
 * 哈希表映射结构
 *
 * @package Tiny.Data.Redis
 *
 * @since 2013-11-30下午04:00:39
 * @final 2013-11-30下午04:00:39
 */
class HashTable extends RedisSchemaBase
{

    /**
     * 删除哈希表里的键
     *
     * @param $key string 键名
     * @return bool
     */
    public function del(string $key):bool
    {
        return (bool)$this->redis->hDel($this->key, $key);
    }

    /**
     * 检测哈希表里的某个键是否存在
     *
     * @param $key string 键
     * @return bool
     */
    public function hExists(string $key):bool
    {
        return (bool)$this->redis->hExists($this->key, $key);
    }

    /**
     * 根据指定键获取哈希表里的值
     *
     * @param $key mixed 键名
     * @return mixed
     */
    public function get($key = null)
    {
        return (is_array($key) ? $this->redis->hMGet($this->key, $key) : $this->redis->hGet($this->key, $key));
    }

    /**
     * 获取哈希表里的所有值 慎用:在表数据巨大的情况下，防止阻塞网络或者耗尽内存
     *
     * @return array
     */
    public function getAll()
    {
        return $this->redis->hGetAll($this->key);
    }

    /**
     * 自增
     *
     * @param $key string 自增
     * @return void
     */
    public function incr(string $key, int $step = 1)
    {
        return $this->redis->hIncrBy($this->key, $key, $step);
    }

    /**
     * 设置键值
     *
     * @param string $key 键名 为array时 为设置多值
     * @param $value mixed 值 默认为null
     * @return bool
     */
    public function set($key, $value = null)
    {
        return (is_array($key) ? $this->redis->hMSet($this->key, $key) : $this->redis->hSet($this->key, $key, $value));
    }

    /**
     * 创建一个基于hashtable的计数器
     * 
     * @param string $hkey 哈希表键
     * @return HashTableCounter
     */
    public function createrCounter(string $hkey)
    {
        return new HashTableCounter($this, $hkey);
    }
}
?>