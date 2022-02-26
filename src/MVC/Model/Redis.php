<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Redis.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-29上午09:43:28
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-29上午09:43:28 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Model;

use Tiny\Data\Redis\Redis as RedisAdapter;
use Tiny\Data\Redis\Schema\Counter;
use Tiny\Data\Redis\Schema\Queue;
use Tiny\Data\Redis\SortSet;
use Tiny\Data\Redis\Set;
use Tiny\Data\Redis\HashTable;

/**
 * Redis的模型类
 *
 * @package Tiny.Data
 * @since 2013-11-29下午05:07:17
 * @final 2013-11-29下午05:07:17
 */
class Redis extends Model
{

    /**
     * 数据操作实例
     *
     * @var RedisAdapter
     */
    protected $redis;

    /**
     * 数据池操作ID
     *
     * @var string
     */
    protected $dataId;

    /**
     * 构造函数
     *
     *
     * @param $id string
     *        data实例ID
     * @return void
     */
    public function __construct($id = 'default')
    {
        if (!$this->dataId)
        {
            $this->dataId = $id;
        }
    }

    /**
     * 返回连接后的类或者句柄
     *
     * @return Redis
     */
    public function getConnector()
    {
        return $this->getRedis()->getConnector();
    }

    /**
     * 关闭或者销毁实例和链接
     *
     * @return void
     */
    public function close()
    {
        return $this->getRedis()->close();
    }
    
    /**
     * 获取计数器实例
     *
     * @param string $key
     *        键
     * @return Counter
     */
    public function createCounter($key)
    {
        return $this->getRedis()->createCounter($key);
    }

    /**
     * 创建一个队列对象
     *
     * @return Queue
     */
    public function createQueue($key)
    {
        return $this->getRedis()->createQueue($key);
    }

    /**
     * 创建一个哈希表对象
     *
     * @return HashTable
     */
    public function createHashTable($key)
    {
        return $this->getRedis()->createHashTable($key);
    }

    /**
     * 创建一个集合对象
     *
     * @return Set
     */
    public function createSet($key)
    {
        return $this->getRedis()->createSet($key);
    }

    /**
     * 创建一个有序集合对象
     *
     * @return SortSet
     */
    public function createSortSet($key)
    {
        return $this->getRedis()->createSortSet($key);
    }

    /**
     * 调用Schema自身函数
     *
     * @param string $method
     *        函数名称
     * @param array $params
     *        参数数组
     * @return
     *
     */
    public function __call(string $method, array $params)
    {
        return call_user_func_array([
            $this->getRedis(),
            $method
        ], $params);
    }

    /**
     * 获取数据操作实例
     *
     * @return RedisAdapter
     */
    protected function getRedis()
    {
        if (!$this->redis)
        {
        $this->redis = $this->data->getDataSource($this->dataId);
        if (!$this->redis instanceof RedisAdapter)
        {
            throw new ModelException('Data.Redis.Schema实例加载失败，ID' . $this->_dataId . '不是Tiny\Data\Redis\Schema实例');
        }
        }
        return $this->redis;
    }
}
?>