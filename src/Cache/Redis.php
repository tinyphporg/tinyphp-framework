<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name RedisCache.php
 * @author King
 * @version Beta 1.0
 * @Date Fri Jan 20 13:02:18 CST 2012
 * @Description Redis缓存实例
 * @Class List
 *        1.Redis redis缓存类
 * @History <author> <time> <version > <desc>
 *          King Fri Jan 20 13:02:18 CST 2012 Beta 1.0 第一次建立该文件
 *          King 2020年02月24日19:49:00 stable 1.0.01 审定稳定版本
 *
 */
namespace Tiny\Cache;

/**
 * Redis缓存类
 *
 * @package Tiny.Cache
 * @since Sat Jan 21 17:32:07 CST 2012
 * @final Sat Jan 21 17:32:07 CST 2012
 *        King 2020年02月24日19:49:00 stable 1.0.01 审定稳定版本
 */
use Tiny\Data\Redis\Redis as RedisSchema;
use Tiny\Tiny;

/**
 * Redis缓存操作类
 *
 * @package Tiny.Cache
 * @since 2013-12-1下午03:32:18
 * @final 2013-12-1下午03:32:18
 */
class Redis implements ICache, \ArrayAccess
{

    /**
     * redis连接句柄
     *
     * @var  \Redis
     */
    protected $_connector;

    /**
     * 缓存策略数组
     *
     * @var array
     */
    protected $_policy = array(
        'lifetime' => 3600
    );

    /**
     * 初始化构造函数
     *
     * @param array $policy
     *        代理数组
     * @return void
     *
     */
    function __construct(array $policy = [])
    {
        $this->_policy = array_merge($this->_policy, $policy);
        if (!$this->_policy['dataid'])
        {
            throw new CacheException('Cache.Redis实例化失败:dataid没有设置');
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
     * @param mixed $key
     *        获取缓存的键名 如果$key为数组 则可以批量获取缓存
     * @return mixed
     */
    public function get($key)
    {
        $connector = $this->_getConnector();
        if (!is_array($key))
        {
            return $connector->get((string)$key);
        }
        $res = $connector->mGet($key);
        $ret = [];
        foreach ($key as $k => $v)
        {
            $ret[$v] = $res[$k];
        }
        return $ret;
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
            $life = $value;
        }

        $life = (int)$life ?: $this->_policy['lifetime'];

        $connector = $this->_getConnector();
        if (!is_array($key))
        {
            return $connector->setex((string)$key, $life, $value);
        }
        $connector->multi(\Redis::PIPELINE);
        foreach ($key as $k => $v)
        {
            $connector->setex($k, $life, $v);
        }
        $connector->exec();
    }

    /**
     * 通过默认的缓存实例判断缓存是否存在
     *
     * @param string $key
     *        键
     * @return bool
     */
    public function exists($key)
    {
        return $this->_getConnector()->exists($key);
    }

    /**
     * 通过默认的缓存实例移除缓存
     *
     * @param string $key
     *        缓存的键 $key为array时 可以批量删除
     * @return bool
     */
    public function remove($key)
    {
        return $this->_getConnector()->delete($key);
    }

    /**
     * 清除所有缓存
     *
     * @return bool
     */
    public function clean()
    {
        return $this->_getConnector()->flushDB();
    }

    /**
     * 数组接口之设置
     *
     * @param $key string
     *        键
     * @param $value mixed
     *        值
     * @return void
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
     * 获取链接
     *
     * @return RedisSchema
     */
    protected function _getConnector()
    {
        if ($this->_connector)
        {
            return $this->_connector;
        }
        $data = Tiny::getApplication()->getData();
        $dataId = $this->_policy['dataid'];
        $redis = $data[$dataId];
        if (!$redis instanceof RedisSchema)
        {
            throw new CacheException("dataid:{$dataId}不是Tiny\Data\Redis\Schema的实例");
        }
        $this->_connector = $redis->getConnector();
        return $this->_connector;
    }
}
?>