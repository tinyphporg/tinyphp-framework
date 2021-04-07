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
               King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Data\Redis;

/**
 * 哈希表映射结构
 *
 * @package Tiny.Data.Redis
 *
 * @since 2013-11-30下午04:00:39
 * @final 2013-11-30下午04:00:39
 */
class HashTable extends Base
{

    /**
     * 删除哈希表里的键
     *
     * @param $key string 键名
     * @return bool
     */
    public function del(string $key):bool
    {
        return (bool)$this->_redis->hDel($this->_key, $key);
    }

    /**
     * 检测哈希表里的某个键是否存在
     *
     * @param $key string 键
     * @return bool
     */
    public function hExists(string $key):bool
    {
        return (bool)$this->_redis->hExists($this->_key, $key);
    }

    /**
     * 根据指定键获取哈希表里的值
     *
     * @param $key mixed 键名
     * @return mixed
     */
    public function get($key)
    {
        return (is_array($key) ? $this->_redis->hMGet($this->_key, $key) : $this->_redis->hGet($this->_key, $key));
    }

    /**
     * 获取哈希表里的所有值 慎用:在表数据巨大的情况下，防止阻塞网络或者耗尽内存
     *
     * @return array
     */
    public function getAll()
    {
        return $this->_redis->hGetAll($this->_key);
    }

    /**
     * 自增
     *
     * @param $key string 自增
     * @return void
     */
    public function incr(string $key, int $step = 1)
    {
        return $this->_redis->hIncrBy($this->_key, $key, $step);
    }

    /**
     * 设置键值
     *
     * @param string $key 键名 为array时 为设置多值
     * @param $value mixed 值 默认为null
     * @return bool
     */
    public function set($key, $value = NULL)
    {
        return (is_array($key) ? $this->_redis->hMSet($this->_key, $key) : $this->_redis->hSet($this->_key, $key, $value));
    }

    /**
     * 求差集并保存在指定的键里
     *
     * @param string $outKey 保存差集的键
     * @param string $key 求差集的键
     * @return bool
     */
    public function createrCounter($key)
    {
        return new HashTable\Counter($this, $key);
    }
}
?>