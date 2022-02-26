<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name SortSet.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-30下午05:18:23
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-30下午05:18:23 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Redis;

use Tiny\Data\Redis\Schema\RedisSchemaBase;

/**
 * redis的有序集合列表
 *
 * @package Tiny.Data.Redis
 *         
 * @since 2013-11-30下午05:18:59
 * @final 2013-11-30下午05:18:59
 */
class SortSet extends RedisSchemaBase
{
    
    /**
     * 添加值到集合中
     *
     * @param int $score 排序的值
     * @param mixed $val 值
     * @return bool
     */
    public function add(int $score, $value)
    {
        return $this->redis->zAdd($this->key, $score, $value);
    }
    
    /**
     * 移除集合里的某个值
     *
     * @param mixed $val 值
     * @return bool
     */
    public function remove($value)
    {
        return $this->redis->zRem($this->key, $value);
    }
    
    /**
     * 按index移除集合里的某个值
     *
     * @param int $start 开始值
     * @param int $end 结束值
     * @return bool
     */
    public function removeByRank(int $start, int $end)
    {
        return $this->redis->zRemRangeByRank($this->key, $start, $end);
    }
    
    /**
     * 按score移除集合里的某个值
     *
     * @param int $start 开始的score
     * @param int $end 结束的score
     * @return bool
     */
    public function removeByScore(int $start, int $end)
    {
        return $this->redis->zRemRangeByScore($this->key, $start, $end);
    }
    
    /**
     * 集合的元素数目
     *
     * @return int
     */
    public function size()
    {
        return $this->redis->zSize($this->key);
    }
    
    /**
     * 统计在score区间的值的个数
     *
     * @param int $start 开始的score
     * @param int $end 结束的score
     * @return int
     */
    public function count($start, $end)
    {
        return $this->redis->zCount($this->key, $start, $end);
    }
    
    /**
     * 自增有序集合里指定值的score
     *
     * @param mixed $val 值
     * @param int $step 增加的score
     * @return int
     */
    public function incr($val, int $step = 1)
    {
        return $this->redis->zIncrBy($this->key, $step, $val);
    }
    
    /**
     * 返回index从$start到$end并按从小到大排序的元素
     *
     * @param int $start 开始的score值
     * @param int $end 结束的score值
     * @param bool $withScores 是否输出score值，默认不输出
     * @return array
     */
    public function range(int $start, int $end, $withScores = false)
    {
        return $this->redis->zRange($this->key, $start, $end, $withScores);
    }
    
    /**
     * 返回index$start到$end并按从大到小排序的元素
     *
     * @param int $start 开始的索引
     * @param int $end 结束的索引
     * @param bool $withScores 是否输出score值，默认不输出
     * @return array
     */
    public function revRange(int $start, int $end, $withScores = false)
    {
        return $this->redis->zRevRange($this->key, $start, $end, $withScores);
    }
    
    /**
     * 返回名称为key的zset中score >= star且score <= end的所有元素
     *
     * @param int $start 开始的score值
     * @param int $end 结束的score值
     * @param array $options 选项
     * @return array
     */
    public function rangeByScore(int $start, int $end, array $options = [])
    {
        return $this->redis->zRangeByScore($this->key, $start, $end, $options);
    }
    
    /**
     * 根据值返回按score从小到大排序好的index
     *
     * @param mixed $val 值
     * @return array
     */
    public function rank($val)
    {
        return $this->redis->zRank($this->key, $val);
    }
    
    /**
     * 根据值返回按score从大到小排序好的index
     *
     * @param mixed $val 值
     * @return array
     */
    public function revRank($val)
    {
        return $this->redis->zRevRank($this->key, $val);
    }
    
    /**
     * 求交集
     *
     * @param mixed $key 键
     * @param array $skeys 其他需要求交集的键
     * @return bool
     */
    public function inter($key, ...$skeys)
    {
        return $this->redis->zInter($key, ...$skeys);
    }
    
    /**
     * 求并集
     *
     * @param mixed $key 键
     * @param array $skeys 其他需要求并集的键
     * @return array
     */
    public function union($key, ...$skeys)
    {
        return $this->redis->zUnion($key, $skeys);
    }
    
    /**
     * 获取某个值的Score
     *
     * @param mixed $val 值
     * @return float
     */
    public function score($val)
    {
        return $this->redis->ZScore($this->key, $val);
    }
    
    /**
     * 获取集合的所有成员
     *
     * @return array
     */
    public function getMembers()
    {
        return $this->redis->zGetMembers($this->key);
    }
}
?>