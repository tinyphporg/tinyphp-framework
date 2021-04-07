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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\Model;

use Tiny\Data\Redis\Redis as RedisSchema;

/**
 * Redis的模型类
 *
 * @package Tiny.Data
 * @since 2013-11-29下午05:07:17
 * @final 2013-11-29下午05:07:17
 */
class Redis extends Base
{

    /**
     * 数据操作实例
     *
     * @var RedisSchema
     */
    protected $_schema;

    /**
     * 数据池操作ID
     *
     * @var string
     */
    protected $_dataId = NULL;

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
        if (NULL == $this->_dataId)
        {
            $this->_dataId = $id;
        }
    }

    /**
     * 返回连接后的类或者句柄
     *
     * @return Redis
     */
    public function getConnector()
    {
        return $this->_getSchema()->getConnector();
    }

    /**
     * 关闭或者销毁实例和链接
     *
     * @return void
     */
    public function close()
    {
        return $this->_getSchema()->close();
    }

    /**
     * 获取字符串实例
     *
     * @param string $key
     *        键
     * @return string
     */
    public function createString($key)
    {
        return $this->_getSchema()->createString($key);
    }

    /**
     * 获取计数器实例
     *
     * @param string $key
     *        键
     * @return \Tiny\Data\Redis\Counter
     */
    public function createCounter($key)
    {
        return $this->_getSchema()->createCounter($key);
    }

    /**
     * 创建一个队列对象
     *
     * @return \Tiny\Data\Redis\Queue
     */
    public function createQueue($key)
    {
        return $this->_getSchema()->createQueue($key);
    }

    /**
     * 创建一个哈希表对象
     *
     * @return \Tiny\Data\Redis\HashTable
     */
    public function createHashTable($key)
    {
        return $this->_getSchema()->createHashTable($key);
    }

    /**
     * 创建一个集合对象
     *
     * @return \Tiny\Data\Redis\Set
     */
    public function createSet($key)
    {
        return $this->_getSchema()->createSet($key);
    }

    /**
     * 创建一个有序集合对象
     *
     * @return \Tiny\Data\Redis\Set
     */
    public function createSortSet($key)
    {
        return $this->_getSchema()->createSortSet($key);
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
    public function __call($method, $params)
    {
        return call_user_func_array([
            $this->_getSchema(),
            $method
        ], $params);
    }

    /**
     * 获取数据操作实例
     *
     * @return RedisSchema
     */
    protected function _getSchema()
    {
        if ($this->_schema)
        {
            return $this->_schema;
        }
        $this->_schema = $this->data->getData($this->_dataId);
        if (!$this->_schema instanceof RedisSchema)
        {
            throw new ModelException('Data.Redis.Schema实例加载失败，ID' . $this->_dataId . '不是Tiny\Data\Redis\Schema实例');
        }
        return $this->_schema;
    }
}
?>