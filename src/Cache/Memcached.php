<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name memcached.php
 * @author King
 * @version Beta 1.0
 * @Date: Fri Dec 16 22 48 00 CST 2011
 * @Description
 * @Class List
 *        1.
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Fri Dec 16 22:48:00 CST 2011 Beta 1.0 第一次建立该文件
 *          King 2020年02月24日17:09:00 stable 1.0.01 审定稳定版本
 */
namespace Tiny\Cache;

use Tiny\Tiny;
use Tiny\Data\Memcached\Memcached as MemcachedSchema;


/**
 * Memcache缓存
 *
 * @package Tiny.Cache
 * @since Fri Dec 16 22 48 07 CST 2011
 * @final Fri Dec 16 22 48 07 CST 2011
 *        King 2020年02月24日17:09:00 stable 1.0.01 审定稳定版本
 */
class Memcached implements ICache, \ArrayAccess
{

    /**
     * memcached操作实例
     *
     * @var MemcachedSchema
     */
    protected $_schema;

    /**
     * 缓存策略数组
     *
     * @var array
     */
    protected $_policy = [
        'lifetime' => 3600
    ];

    /**
     * 初始化构造函数
     *
     * @param array $policy
     *        代理数组
     * @return void
     */
    function __construct(array $policy = [])
    {
        $this->_policy = array_merge($this->_policy, $policy);
        if (!$this->_policy['dataid'])
        {
            throw new CacheException('Cache.Memcached实例化失败:dataid没有设置');
        }
    }

    /**
     * 获取策略数组
     *
     * @return array
     */
    public function getPolicy()
    {
        return $this->_policy;
    }

    /**
     * 获取缓存
     *
     * @param
     *        string || array $key 获取缓存的键名 如果$key为数组 则可以批量获取缓存
     * @return mixed
     */
    public function get($key)
    {
        return $this->_getSchema()->get($key);
    }

    /**
     * 设置缓存
     *
     * @param string $key
     *        缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value
     *        缓存的值 $key为array时 为设置生命周期的值
     * @param int $life
     *        缓存的生命周期
     * @return bool
     */
    public function set($key, $value = NULL, $life = NULL)
    {
        if (is_array($key))
        {
            $value = (int)$value ?: $this->_policy['lifetime'];
            $life = NULL;
        }
        return $this->_getSchema()->set($key, $value, $life);
    }

    /**
     * 判断缓存是否存在
     *
     * @param string $key
     *        键
     * @return bool
     */
    public function exists($key)
    {
        return $this->_getSchema()->get($key) ? TRUE : FALSE;
    }

    /**
     * 移除缓存
     *
     * @param string $key
     *        缓存的键 $key为array时 可以批量删除
     * @return bool
     */
    public function remove($key)
    {
        return $this->_getSchema()->delete($key);
    }

    /**
     * 清除所有缓存
     *
     * @param
     *        void
     * @return bool
     */
    public function clean()
    {
        return $this->_getSchema()->flush();
    }

    /**
     * 数组接口之设置
     *
     * @param $key string
     *        键
     * @param $value mixed
     *        值
     * @return
     *
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * 数组接口之获取缓存实例
     *
     * @param $key string
     *        键
     * @return array
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * 数组接口之是否存在该值
     *
     * @param $key string
     *        键
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * 数组接口之移除该值
     *
     * @param $key string
     *        键
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * 获取memcached操作实例
     *
     * @return MemcachedSchema
     */
    protected function _getSchema()
    {
        if ($this->_schema)
        {
            return $this->_schema;
        }
        $data = Tiny::getApplication()->getData();
        $dataId = $this->_policy['dataid'];
        $schema = $data[$dataId];
        if (!$schema instanceof MemcachedSchema)
        {
            throw new CacheException("dataid:{$dataId}不是Tiny\Data\Memcached\Memcached的实例");
        }
        $this->_schema = $schema;
        return $schema;
    }
}