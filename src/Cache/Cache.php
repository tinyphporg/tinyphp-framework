<?php
declare (strict_types = 1); 
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
* 缓存池接口
* 
* @package Tiny.Cache
* @since 2021年11月26日下午2:36:25
* @final 2021年11月26日下午2:36:25
*
*/
interface CacheItemPoolInterface
{
    
}

/**
* 缓存键值对接口
* 
* @package Tiny.Cache
* @since 2021年11月26日下午2:36:48
* @final 2021年11月26日下午2:36:48
*
*/
interface CacheItemInterface
{
    
}

/**
 * 缓存接口
 * 遵循psr/simple-cache规范，并在此基础上，增加严格的类型约束
 *
 * @package Tiny.Cache
 * @since Fri Dec 16 22 29 08 CST 2011
 * @final Fri Dec 16 22 29 08 CST 2011
 *        King 2020年02月24日上午12:06:00 stable 1.0 审定稳定版本
 *        King 2021年11月26日下午2:55:38 更新为遵守psr-6的simple-cache规范
 */
interface CacheInterface
{

    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     * 
     * @return mixed key存在时返回缓存值，不存在时返回$default
     * 
     * @throws InvalidArgumentException Key不存在时返回该异常
     */
    public function get($key, $default = null);

    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     * 
     * @return bool
     * 
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function set($key, $value = null, int $ttl = null): bool;

    /**
     * 移除缓存
     *
     * @param string $key 删除key对应的缓存值
     * 
     * @return bool 删除成功返回true, 失败则为false
     * 
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function delete($key): bool;

    /**
     * 清空所有的缓存字典
     *
     * @return bool 成功为true否则为false
     */
    public function clear();
    
    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     *
     * @return mixed key存在时返回缓存值，不存在时返回$default
     *
     * @throws InvalidArgumentException Key不存在时返回该异常
     */
    public function getMultiple(array $keys, array $default = null);
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     *
     * @return bool
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function setMultiple(array $values, int $ttl = null);
    
    /**
     * 删除缓存
     *
     * @param array $key 删除key对应的缓存值
     *
     * @return bool 键数组成功删除则返回true，否则为false
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function deleteMultiple(array $keys);
 
    /**
     * 缓存是否存在
     *
     * @param string $key 缓存键
     *
     * @return bool 存在返回true 否则为false
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function has($key);
}


/**
 * Cache管理 mvc
 *
 * @package : Cache 缓存适配器
 * @since : Sat Dec 17 17:18:19 CST 2011
 * @final : Sat Dec 17 17:18:19 CST 2011
 */
class Cache implements CacheInterface, \ArrayAccess
{

    /**
     * Single instance
     *
     * @var Cache
     */
    protected static $instance;

    /**
     * Mapping array of cache driver
     *
     * @var array
     */
    protected static $storageAdapters = [
        'file' => Tiny\Cache\File::class,
        'memcached' => Tiny\Cache\Memcached::class,
        'redis' => Tiny\Cache\Redis::class
    ];
    
    /**
     * Default cache instance ID
     *
     * @var string
     */
    protected $defaultStorageId = 'default';

    /**
     * Mapping array for cache policy
     *
     * @var array
     */
    protected $storages = [];

    /**
     * 单一模式，获取实例
     *
     * @return Cache
     */
    public static function getInstance()
    {
        if (!self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }    
    
    /**
     * 注册缓存适配器
     *
     * @param string $storageId 存储id
     * @param string $adapterName 适配器的类名
     * 
     * @return void
     * 
     * @throws CacheException $storageId存在的情况抛出异常
     */
    public static function regStorageAdapter($storageId, $adapterName)
    {
        if (key_exists($storageId, self::$storageAdapters))
        {
            throw new CacheException("Failed to register new cache storage adapter: storageid [{$storageId}]is exists!");
        }
        self::$storageAdapters[$storageId] = $adapterName;
    }

    /**
     * 增加一个缓存存储的适配器配置
     *
     * @param array $cfg 配置数组
     * 
     * @return bool true 添加成功时返回true 否则为false
     * 
     * @throws CacheException 配置参数异常时抛出
     *
     */
    public function addStorageAdapter(array $cfg)
    {
        //[ 'id' => storageid, 'storage' => 'file', 'options' => 'ssss']
        $id = (string)$cfg['id'];  
        if (!$cfg['id'])
        {
            throw new CacheException('config.id为无效参数');
        }
        if (key_exists($id, $this->storages))
        {
            throw new CacheException(sprintf('config.id:%s已存在 ', $id));
        }
        
        $storageId = (string)$cfg['storage'];
        if (!key_exists($storageId, self::$storageAdapters))
        {
            throw new CacheException(sprintf('config.storage:%s 不存在', $storageId));
        }
        $options = (array)$cfg['options'];
        $this->storages[$id] = [
            'adapterName' => self::$storageAdapters[$storageId],
            'storageId' => $storageId,
            'instance' => null,
            'options' => $options,
        ];
        return true;
    }

    /**
     * 设置默认的缓存ID
     *
     * @param string $id 缓存适配器的存储ID
     * @return void
     */
    public function setDefaultStorageId($id)
    {
        $this->defaultStorageId = (string)$id;
    }

    /**
     * 获取默认的缓存ID
     *
     * @return string
     * 
     */
    public function getDefaultStorageId()
    {
        return $this->_defaultStorageId;
    }

    /**
     * 根据ID获取一个缓存实例
     *
     * @param string $id storageid
     * @return CacheStorageAdapterBase
     */
    public function storageAdapter($id = null)
    {
        if (!$this->storages)
        {
            throw new CacheException('存储适配器的配置为空');
        }
        $id = $id ?: $this->defaultStorageId;
        if (!key_exists($id, $this->storages) || !is_array($this->storages[$id]) || empty($this->storages[$id]))
        {
            throw new CacheException(sprintf('获取ID为%s的缓存实例失败：该缓存策略ID $id:%s没有配置profile.cache.config或者不是一个array!', $id, $id));
        }
        
        $config = & $this->storages[$id];
        if ($config['instance'])
        {
            return $config['instance'];
        }
        
        $adapterName = $config['adapterName'];
        $options = $config['options'];
        $storageInstance = new $adapterName($options);
        
        if (!$storageInstance instanceof CacheStorageAdapterBase)
        {
            throw new CacheException(sprintf('%s实例没有实现ICache接口!', $adapterName));
        }
        $config['instance'] = $storageInstance;
        return $storageInstance;
    }

    
    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     *
     * @return mixed key存在时返回缓存值，不存在时返回$default
     *
     * @throws InvalidArgumentException Key不存在时返回该异常
     */
    public function get($key, $default = null)
    {
        
    }
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     *
     * @return bool
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function set($key, $value = null, int $ttl = null): bool
    {
        
    }
    
    /**
     * 移除缓存
     *
     * @param string $key 删除key对应的缓存值
     *
     * @return bool 删除成功返回true, 失败则为false
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function delete($key): bool
    {
        
    }
    
    /**
     * 清空所有的缓存字典
     *
     * @return bool 成功为true否则为false
     */
    public function clear()
    {
        
    }
    
    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     *
     * @return mixed key存在时返回缓存值，不存在时返回$default
     *
     * @throws InvalidArgumentException Key不存在时返回该异常
     */
    public function getMultiple(array $keys, array $default = null): array
    {
        
    }
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     *
     * @return bool
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function setMultiple(array $values, int $ttl = null): bool
    {
        
    }
    
    /**
     * 删除缓存
     *
     * @param array $key 删除key对应的缓存值
     *
     * @return bool 键数组成功删除则返回true，否则为false
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function deleteMultiple(array $keys): bool
    {
        
    }
    
    /**
     * 缓存是否存在
     *
     * @param string $key 缓存键
     *
     * @return bool 存在返回true 否则为false
     *
     * @throws InvalidArgumentException Key不合法时返回该异常
     */
    public function has($key)
    {
        
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


abstract class CacheStorageAdapterBase
{
    
}
?>