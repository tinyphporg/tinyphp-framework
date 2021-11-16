<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Queue.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-30下午04:02:04
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-30下午04:02:04 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Redis;

/**
 * 队列
 *
 * @package Tiny.Data.Redis
 * @since 2013-11-30下午04:02:36
 * @final 2013-11-30下午04:02:36
 */
class Queue extends Base
{
    
    /**
     * 取出一个值
     *
     * @param int $seconds 阻塞秒数
     * @return mixed
     */
    public function pop(int $seconds = 0)
    {
        return (($seconds > 0) ? $this->_redis->brPop($this->_key, $seconds) : $this->_redis->rpop($this->_key));
    }
    
    /**
     * 按序列取值
     *
     * @param int $start 起始位置
     * @param int $limit 取值间隔
     * @return array
     */
    public function range($start = 0, $limit = -1)
    {
        return $this->_redis->lrange($this->_key, $start, $limit);
    }
    
    /**
     * 压入一个值
     *
     * @param mixed $val 压入的值
     * @return bool
     */
    public function push($val)
    {
        return $this->_redis->lPush($this->_key, $val);
    }
    
    /**
     * 获取队列长度
     *
     * @return int
     */
    public function length()
    {
        return $this->_redis->lLen($this->_key);
    }
    
    /**
     * 删除队列
     *
     * @return bool
     */
    public function del()
    {
        return $this->_redis->delete($this->_key);
    }
}
?>