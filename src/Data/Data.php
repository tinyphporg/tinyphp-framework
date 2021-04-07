<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name Data.php
 * @author King
 * @version Beta 1.0
 * @Date Sun Dec 25 23:35:04 CST 2011
 * @Description 数据池管理
 * @Class List:
 *        1.Data 数据池管理类 管理所有的数据源
 * @Function List:
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Sun Dec 25 23:35:04 CST 2011 Beta 1.0 第一次建立该文件
 *          King 2020年3月2日11:31 stable 1.0.01 审定
 *
 */
namespace Tiny\Data;

/**
 * 数据库模型操作类
 *
 * @package Tiny.Data
 * @since Wed Dec 28 09:09:58 CST 2011
 * @final Wed Dec 28 09:09:58 CST 2011
 *        King 2020年3月2日11:31 stable 1.0.01 审定
 */
class Data implements \ArrayAccess
{

    /**
     * DATA池实例 单例模式
     *
     * @var Data
     */
    protected static $_instance;

    /**
     * 已经注册的网络连接器映射
     *
     * @var array
     */
    protected static $_driverMap = [
        'db' => 'Tiny\Data\Db\Db',
        'memcached' => 'Tiny\Data\Memcached\Memcached',
        'redis' => 'Tiny\Data\Redis\Redis'
    ];

    /**
     * Data 配置策略数组
     *
     * @var array
     */
    protected $_policys = [];

    /**
     * 默认使用的Data池 数据源ID
     *
     * @var string
     */
    protected $_defaultId = 'default';

    /**
     * 已经实例化的Data实例
     *
     * @var array
     */
    protected $_datas = [];

    /**
     * 单一模式，获取Data实例
     *
     * @return Data 数据池单一实例
     */
    public static function getInstance()
    {
        if (!self::$_instance)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 注册数据源驱动
     *
     * @param string $id
     *        数据源ID
     * @param string $className
     *        数据源操作类名称
     * @return void
     */
    public static function regDriver($id, $className)
    {
        if (self::$_driverMap[$id])
        {
            throw new DataException(sprintf('添加数据驱动失败:ID%s已经存在', $id));
        }
        self::$_driverMap[$id] = $className;
    }

    /**
     * 增加一个数据策略
     *
     * @param array $policy
     *        数据池配置数组
     * @return void
     */
    public function addPolicy(array $policy)
    {
        $id = $policy['id'];
        if (!isset($id))
        {
            throw new DataException('添加数据策略错误:Data.id没有设置');
        }

        if (strpos($policy['driver'], '.') > -1)
        {
            $ds = explode('.', $policy['driver']);
            $policy['driver'] = $ds[0];
            $policy['schema'] = $ds[1];
        }
        $className = self::$_driverMap[$policy['driver']];
        if (!$className)
        {
            throw new DataException(sprintf('添加数据策略错误:Data.driver %s 没有注册', $policy['driver']));
        }

        $policy['className'] = $className;
        $this->_policys[$id] = $policy;
    }

    /**
     * 设置默认的default 数据源操作ID
     *
     * @param string $id
     *        数据操作实例ID
     * @return void
     */
    public function setDefaultId(string $id)
    {
        if (!key_exists($id, $this->_policys))
        {
            throw new DataException(sprintf('设置默认的数据策略ID失败:%s不存在', $id));
        }
        $this->_defaultId = $id;
    }

    /**
     * 获取默认的数据源操作ID
     *
     * @return string
     */
    public function getDefaultId()
    {
        return $this->_defaultId;
    }

    /**
     * 根据数据策略的ID标识获取一个数据句柄
     *
     * @param string $id
     *        Data池的数据源id
     * @return IDataSchema
     */
    public function getData($id = NULL)
    {
        if (NULL == $id)
        {
            $id = $this->_defaultId;
        }

        if (!key_exists($id, $this->_policys))
        {
            throw new DataException(sprintf('获取Data实例错误:该数据策略ID[%s]不存在!', $id));
        }

        if ($this->_datas[$id])
        {
            return $this->_datas[$id];
        }

        $policy = $this->_policys[$id];
        $className = $policy['className'];
        unset($policy['className'], $policy['id']);
        $dataSchema = new $className($policy);
        if (!$dataSchema instanceof IDataSchema)
        {
            throw new DataException(sprintf('获取Data实例错误:%s没有实现接口Tiny\Data\IDataSchema!', $className));
        }
        $this->_datas[$id] = $dataSchema;
        return $dataSchema;
    }

    /**
     * 数组接口之设置
     *
     * @param string $id
     *        data标示
     * @param IDataSchema $dataSchema
     *        数据源操作类
     * @return void
     */
    public function offsetSet($id, $dataSchema)
    {
        if (NULL == $id)
        {
            throw new DataException('$id不允许为空!');
        }
        if (!$dataSchema instanceof IDataSchema)
        {
            throw new DataException('data schema 需要为实现了ISchema的实例');
        }
        $this->_datas[$id] = $dataSchema;
    }

    /**
     * 数组接口 获取数据源操作实例
     *
     * @param string $id
     *        数据源ID
     * @return IDataSchema
     */
    public function offsetGet($id)
    {
        return $this->getData($id);
    }

    /**
     * 数组接口 是否存在数据源ID
     *
     * @param string $id
     *        键
     * @return bool
     */
    public function offsetExists($id)
    {
        return (bool)$this->getData($id);
    }

    /**
     * 数组接口 移除该值 不允许移除
     *
     * @param string $id
     *        数据源ID
     * @return void
     */
    public function offsetUnset($id)
    {
        throw new DataException("data池 不允许通过ID移除数据源");
    }

    /**
     * 代理默认的数据库连接
     *
     * @param string $method
     *        函数名
     * @param array $args
     *        参数数组
     * @return mixed
     */
    public function __call($method, $args)
    {
        $data = $this->getData();
        $ret = call_user_func_array([
            $data,
            $method
        ], $args);
        return $ret;
    }

    /**
     * 限制构造函数只能自身创建实例，以满足单例模式的强制约束
     *
     * @return void
     */
    protected function __construct()
    {
    }
}
?>