<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @Name: Cache.php
 * @Author: King
 * @Version: Beta 1.0
 * @Date: Sat Dec 17 12:29:10 CST 2011
 * @Description:缓存主体类
 * @Class List:
 *        1.Cache 缓存主体类 策略模式
 * @History: <author> <time> <version > <desc>
 *           King Sat Dec 17 12:29:10 CST 2011 Beta 1.0 第一次建立该文件
 *           King 2020年02月24日上午11:42:00 stable 1.0 审定稳定版本
 */
namespace Tiny\Cache;

/**
 * Cache
 *
 * @package : Cache 缓存适配器
 * @since : Sat Dec 17 17:18:19 CST 2011
 * @final : Sat Dec 17 17:18:19 CST 2011
 */
class Cache implements ICache, \ArrayAccess
{

    /**
     * Mapping array of cache driver
     *
     * @var array
     */
    protected static $_driverMap = [
        'file' => 'Tiny\Cache\File',
        'memcached' => 'Tiny\Cache\Memcached',
        'redis' => 'Tiny\Cache\Redis'
    ];

    /**
     * Single instance
     *
     * @var Cache
     */
    protected static $_instance;

    /**
     * Default cache instance ID
     *
     * @var string
     */
    protected $_defaultId = 'default';

    /**
     * Mapping array for cache policy
     *
     * @var array
     */
    protected $_policys = [];

    /**
     * 注册缓存适配器的驱动类
     *
     * @param string $type
     *        缓存配置的类型名称
     * @param
     *        string
     * @return void
     */
    public static function regDriver($type, $className)
    {
        if (isset(self::$_driverMapp[$type]))
        {
            throw new CacheException('Failed to register cache driver: driver type[' . $type . ']is exists!');
        }
        self::$_driverMap[$type] = $className;
    }

    /**
     * 单一模式，获取实例
     *
     * @return Cache
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
     * 注册一个缓存策略
     *
     * @param array $prolicy
     *        策略数组
     * @return void
     *
     */
    public function regPolicy(array $policy)
    {
        $id = $policy['id'];
        if (!$id)
        {
            throw new CacheException('Cache策略添加失败：policy需要设置ID作为缓存实例标示');
        }

        $driver = $policy['driver'];
        if (!key_exists($driver, self::$_driverMap))
        {
            throw new CacheException('Cache策略添加失败：driver不存在或者没有设置');
        }

        if ($this->_policys[$id])
        {
            throw new CacheException('Cache策略添加失败：ID:' . $id . '已存在 ');
        }

        $policy['className'] = self::$_driverMap[$driver];
        $this->_policys[$id] = $policy;
        return TRUE;
    }

    /**
     * 设置默认的缓存ID
     *
     * @param string $id
     *        缓存ID
     * @return void
     */
    public function setDefaultId($id)
    {
        $id = (string)$id;
        if (!key_exists($id, $this->_policys))
        {
            throw new CacheException('设置默认缓存ID失败:ID:' . $id . '不存在');
        }
        $this->_defaultId = $id;
    }

    /**
     * 获取默认的缓存ID
     *
     * @return string
     */
    public function getDefaultId()
    {
        return $this->_defaultId;
    }

    /**
     * 根据缓存策略的身份标识获取一个缓存实例
     *
     * @param string $id
     *        CachePolicy 的 id；
     * @return ICache
     */
    public function getCache($id = NULL)
    {
        if (empty($this->_policys))
        {
            throw new CacheException('Failed to get cache instance: Mapping array for cache policy is empty!');
        }

        if ($id == NULL)
        {
            $id = $this->_defaultId;
        }

        if (!key_exists($id, $this->_policys) || !is_array($this->_policys[$id]) || empty($this->_policys[$id]))
        {
            throw new CacheException('获取ID为' . $id . '的缓存实例失败：该缓存策略ID $id ' . $id . '没有配置profile.cache.prolicy或者不是一个array!');
        }

        $policy = & $this->_policys[$id];
        if ($policy['instance'])
        {
            return $policy['instance'];
        }

        $policy['instance'] = new $policy['className']($policy);
        if (!$policy['instance'] instanceof ICache)
        {
            throw new CacheException($policy['className'] .'实例没有实现ICache接口!');
        }
        return $policy['instance'];
    }

    /**
     * 根据ID设置缓存实例
     *
     * @param string $id
     *        缓存ID
     * @param
     *        ICache 实现了缓存接口的缓存实例
     * @return void
     */
    public function setCache($id, ICache $cache)
    {
        if (key_exists($id, $this->_policys))
        {
            throw new CacheException('设置缓存实例失败:cache ID' . $id . '已存在');
        }
        $this->_policys[$id] = [
            'instance' => $cache
        ];
    }

    /**
     * 通过默认的缓存实例设置缓存
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
        return $this->getCache()->set($key, $value, $life);
    }

    /**
     * 获取缓存
     *
     * @param string $key
     *        获取缓存的键名 如果$key为数组 则可以批量获取缓存
     * @return mixed
     */
    public function get($key)
    {
        return $this->getCache()->get($key);
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
        return $this->getCache()->remove($key);
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
        return $this->getCache()->exists($key);
    }

    /**
     * 清除默认缓存实例的所有缓存
     *
     * @return void
     */
    public function clean()
    {
        return $this->getCache()->clean();
    }

    /**
     * 数组接口之设置
     *
     * @param $key string
     *        键
     * @param $value ICache
     *        实例
     * @return void
     */
    public function offsetSet($id, $cache)
    {
        return $this->setCache($id, $cache);
    }

    /**
     * 数组接口之获取缓存实例
     *
     * @param $key string
     *        键
     * @return ICache
     */
    public function offsetGet($id)
    {
        return $this->getCache($id);
    }

    /**
     * 数组接口之是否存在该值
     *
     * @param $key string
     *        键
     * @return boolean
     */
    public function offsetExists($id)
    {
        return $this->getCache($id) ? TRUE : FALSE;
    }

    /**
     * 数组接口 不允许移除该值
     *
     * @param $key string
     *        键值
     * @return void
     */
    public function offsetUnset($id)
    {
        // unset($this->_policys[$id]);
    }

    /**
     * get cache instance by id
     *
     * @param string $id
     * @return \Tiny\Cache\:
     */
    public function __get($id)
    {
        return $this->getCache($id);
    }

    /**
     * set cache instance
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($id, $cache)
    {
        return $this->setCache($id, $cache);
    }

    /**
     * 构造函数 只能通过Cache::getInstance()获取新实例
     *
     * @param
     *        void
     * @return void
     */
    protected function __construct()
    {
    }
}
?>