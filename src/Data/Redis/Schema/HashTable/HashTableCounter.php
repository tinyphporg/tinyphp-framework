<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Counter.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-30下午01:51:04
 * @Description redis哈希表计数器
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-30下午01:51:04 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Redis\Schema\HashTable;


use Tiny\Data\Redis\Schema\HashTable;

/**
 * 哈希表的计数器
 *
 * @package Tiny.Data.Redis.HashTable
 * @since 2013-11-30下午01:51:35
 * @final 2013-11-30下午01:51:35
 */
class HashTableCounter
{
    
    /**
     * 哈希表操作实例
     *
     * @var HashTable
     */
    protected $hashtable;
    
    /**
     * 操作的哈希表键
     *
     * @var string
     */
    protected $key;
    
    /**
     * 构造函数
     *
     * @param HashTable 哈希表操作实例
     * @param string $key 键名
     * @return void
     */
    public function __construct(HashTable $hashtable, $key)
    {
        $this->hashtable = $hashtable;
        $this->key = $key;
    }
    
    /**
     * 获取字符串的值
     *
     * @param void
     * @return int
     */
    public function get()
    {
        return (int)$this->hashtable->get($this->key);
    }
    
    /**
     * 自增
     *
     * @param int $step 步进 默认为1
     * @return bool
     */
    public function incr($step = 1)
    {
        return $this->hashtable->incr($this->key, $step);
    }
    
    /**
     * 自减
     *
     * @param int $step 步进 默认为1
     * @return bool
     */
    public function decr($step = 1)
    {
        $step = -1 * $step;
        return $this->hashtable->incr($this->key, $step);
    }
    
    /**
     * 重置计数器
     *
     * @return bool
     */
    public function reset()
    {
        return $this->hashtable->set($this->key, 0);
    }
    
    /**
     * 字符串化
     *
     * @param void
     * @return string
     */
    public function __toString()
    {
        return (string)$this->get($this->key);
    }
}
?>