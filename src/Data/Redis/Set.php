<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Set.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-30下午04:34:02
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-30下午04:34:02 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Data\Redis;


/**
 * redis的set结构操作类
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30下午04:34:36
 * @final 2013-11-30下午04:34:36
 */
class Set extends Base
{

    /**
     * 添加值到集合中
     *
     * @param mixed $val
     *        值
     * @return bool
     */
    public function add($val)
    {
        return $this->_redis->sAdd($this->_key, $val);
    }

    /**
     * 移除集合里的某个值
     *
     * @param mixed $val
     *        值
     * @return bool
     */
    public function remove($val)
    {
        return $this->_redis->sRem($this->_key, $val);
    }

    /**
     * 检测某个数值是否是集合的成员
     *
     * @param mixed $val
     *        数值
     * @return bool
     */
    public function contains($val)
    {
        return $this->_redis->sIsMember($this->_key, $val);
    }

    /**
     * 集合的元素数目
     *
     * @return int
     */
    public function size()
    {
        return $this->_redis->sSize($this->_key);
    }

    /**
     * 求差集
     *
     * @param mixed $key
     *        键
     * @param array $skeys
     *        其他需要求差集的键
     * @return array
     */
    public function diff($key, ...$skeys)
    {
        return $this->_redis->sDiff($key, ...$skeys);
    }

    /**
     * 求差集并保存在指定的键里
     *
     * @param string $dstKey
     *        保存差集的键
     * @param string $skeys
     *        求差集的键
     * @return array
     */
    public function diffStore($dstKey, ...$skeys)
    {
        return $this->_redis->sDiffStore($skeys, ...$skeys);
    }

    /**
     * 求交集
     *
     * @param string $key
     *        键
     * @param array $skeys
     *        其他需要求交集的键
     * @return array
     */
    public function inter($key, $skeys)
    {
        return $this->_redis->sInter($key, ...$skeys);
    }

    /**
     * 求交集并保存在指定的键里
     *
     * @param string $dstKey
     *        保存交集的键
     * @param string $skeys
     *        求交集的键
     * @return bool
     */
    public function interStore($dstKey, ...$skeys)
    {
        return $this->_redis->sInterStore($dstKey, ...$skeys);
    }

    /**
     * 求并集
     *
     * @param string $key
     *        目标键
     * @param array $skeys
     *        求并集的其他键
     * @return array
     */
    public function union($key, ...$skeys)
    {
        return $this->_redis->sUnion($key, ...$skeys);
    }

    /**
     * 求并集并保存在指定的键里
     *
     * @param string $key
     *        保存并集的键
     * @param array $skeys
     *        求并集的键
     * @return array
     */
    public function unionStore($key, ...$skeys)
    {
        return $this->_redis->sUnionStore($key, ...$skeys);
    }

    /**
     * 随机返回并删除名称为key的set中一个元素
     *
     * @return mixed
     */
    public function pop()
    {
        return $this->_redis->sPop();
    }

    /**
     * 随机取回一个集合中的值
     *
     * @return mixed
     */
    public function rand()
    {
        return $this->_redis->sRandMember();
    }

    /**
     * 获取集合的所有成员
     *
     * @return array
     */
    public function getMembers()
    {
        return $this->_redis->sGetMembers();
    }
}
?>