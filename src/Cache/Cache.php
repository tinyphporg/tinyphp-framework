<?php
declare(strict_types = 1);
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

use Tiny\Cache\Storager\File;
use Tiny\Cache\Storager\Memcached;
use Tiny\Cache\Storager\Redis;
use Tiny\Cache\Storager\PHP;
use Tiny\Cache\Storager\CacheStorager;
use Tiny\Cache\Storager\SingleCache;

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
     * Mapping array of cache driver
     *
     * @var array
     */
    protected static $storagerMap = [
        'file' => File::class,
        'memcached' => Memcached::class,
        'redis' => Redis::class,
        'php' => PHP::class,
        'singlephp' => SingleCache::class
    ];
    
    /**
     * Default ttl
     *
     * @var integer
     */
    protected $defaultTtl = 60;
    
    /**
     * Default cache instance ID
     *
     * @var string
     */
    protected $defaultId = 'default';
    
    /**
     * Default storage path
     *
     * @var string
     */
    protected $defaultPath = '';
    
    /**
     * Mapping array for cache policy
     *
     * @var array
     */
    protected $storagers = [];

    /**
     * 注册缓存适配器
     *
     * @param string $storageId 存储器id
     * @param string $adapterName 存储器类名
     * @throws CacheException $storageId存在的情况抛出异常
     */
    public static function regStorager(string $storagerId, string $storagerClass)
    {
        if (key_exists($storagerId, self::$storagerMap)) {
            throw new CacheException(sprintf("Failed to register new cache storager: storageid [%s]is exists!", $storagerId));
        }
        self::$storagerMap[$storagerId] = $storagerClass;
    }
    
    /**
     * 获取存储器ID
     *
     * @param string $storagerClass 存储器类名
     * @return string
     */
    public static function getStoragerId(string $storagerClass)
    {
        return array_search($storagerClass, self::$storagerMap);
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
    public function addStorager(string $cacheId, string $storagerId, array $options = [])
    {
        if (key_exists($cacheId, $this->storagers)) {
            throw new CacheException(sprintf('cache.id.%s is exists!', $cacheId));
        }
        
        if (!key_exists($storagerId, self::$storagerMap)) {
            throw new CacheException(sprintf('cache.storager.%s is not exists!', $storagerId));
        }
        
        $options['ttl'] = $options['ttl'] ?? $this->defaultTtl;
        if (!is_int($options['ttl'])) {
            $options['ttl'] = (int)$options['ttl'] ?: $this->defaultTtl;
        }
        
        if (isset($options['path'])) {
            $options['path'] = $options['path'] ?: $this->defaultPath;
        }
        $this->storagers[$cacheId] = [
            'cacheId' => $cacheId,
            'storagerClass' => self::$storagerMap[$storagerId],
            'storagerId' => $storagerId,
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
    public function setDefaultId(string $cacheId)
    {
        $this->defaultId = (string)$cacheId;
    }
    
    /**
     * 获取默认的缓存ID
     *
     * @return string
     *
     */
    public function getDefaultId(): string
    {
        return $this->defaultId;
    }
    
    /**
     * 设置 默认的缓存路径
     *
     * @param string $path
     */
    public function setDefaultPath(string $path)
    {
        $this->defaultPath = $path;
    }
    
    /**
     * 获取默认的缓存路径
     *
     * @return string
     */
    public function getDefaultPath(): string
    {
        return $this->defaultPath;
    }
    
    /**
     * 设置默认的缓存过期时间
     *
     * @param int $ttl
     */
    public function setDefaultTtl(int $ttl)
    {
        $this->defaultTtl = $ttl;
    }
    
    /**
     * 获取默认的生存时间
     *
     * @return number
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
    
    /**
     *  根据存储器类获取存储实例
     *  
     * @param string $className
     * @throws CacheException
     * @return \Tiny\Cache\CacheInterface
     */
    public function getStoragerByClass(string $className) 
    {
        if (!$className) {
            throw new CacheException('Unable to get cache storager:storage classname is empty!');
        }
        $cacheId = array_search($className, array_column($this->storagers, 'storagerClass', 'cacheId'));
        if (!$cacheId) {
            throw new CacheException('Unable to get cache storager:storage classname is not exists!');
        }
        return $this->getStoragerById($cacheId);
    }
    
    /**
     * 根据Cache ID获取一个缓存实例
     *
     * @param string $id storageid
     * @return CacheInterface
     */
    public function getStoragerById(string $cacheId = null)
    {
        if (!$this->storagers) {
            throw new CacheException('Unable to get cache storager:storager array is empty!');
        }
        
        $cacheId = $cacheId ?: $this->defaultId;
        if (!key_exists($cacheId, $this->storagers) || !is_array($this->storagers[$cacheId]) || empty($this->storagers[$cacheId])) {
            throw new CacheException(sprintf('Failed to get cache storager：profile.cache.config.%s is not set or is not an array!', $cacheId));
        }
        
        $config = &$this->storagers[$cacheId];
        if ($config['instance']) {
            return $config['instance'];
        }
        
        $storagerClass = $config['storagerClass'];
        $options = $config['options'];
        $storagerInstance = new $storagerClass($options);
        
        if (!$storagerInstance instanceof CacheInterface) {
            throw new CacheException(sprintf('Cacache storager[%s] is not an instance of %s!', $storagerClass, CacheInterface::class));
        }
        $config['instance'] = $storagerInstance;
        return $storagerInstance;
    }
    
    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     *       
     * @return mixed key存在时返回缓存值，不存在时返回$default
     */
    public function get(string $key, $default = null)
    {
        return $this->getStoragerById()->get($key, $default);
    }
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     *       
     * @return bool
     */
    public function set(string $key, $value = null, int $ttl = 0)
    {
        return $this->getStoragerById()->set($key, $value, $ttl);
    }
    
    /**
     * 移除缓存
     *
     * @param string $key 删除key对应的缓存值
     *       
     * @return bool 删除成功返回true, 失败则为false
     */
    public function delete($key)
    {
        return $this->getStoragerById()->delete($key);
    }
    
    /**
     * 清空所有的缓存字典
     *
     * @return bool 成功为true否则为false
     */
    public function clear()
    {
        return $this->getStoragerById()->clear();
    }
    
    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     *       
     * @return mixed key存在时返回缓存值，不存在时返回$default
     */
    public function getMultiple(array $keys, $default = null)
    {
        return $this->getStoragerById()->getMultiple($keys, $default);
    }
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     *       
     * @return bool
     */
    public function setMultiple(array $values, int $ttl = 0)
    {
        return $this->getStoragerById()->setMultiple($values, $ttl);
    }
    
    /**
     * 删除缓存
     *
     * @param array $key 删除key对应的缓存值
     *       
     * @return bool 键数组成功删除则返回true，否则为false
     */
    public function deleteMultiple(array $keys)
    {
        return $this->deleteMultiple($keys);
    }
    
    /**
     * 缓存是否存在
     *
     * @param string $key 缓存键
     *       
     * @return bool 存在返回true 否则为false
     */
    public function has($key)
    {
        return $this->getStoragerById()->has($key);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($cacheId, $cacheInstance)
    {
        throw new CacheException('It is not permitted to set this property.');
    }
    
    /**
     * 根据Cache ID获取缓存接口
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($cacheId)
    {
        return $this->getStoragerById($cacheId);
    }
    
    /**
     * 是否存在缓存实例
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($cacheId)
    {
        return key_exists($cacheId, $this->storagers);
    }
    
    /**
     * 数组接口 不允许移除该值
     *
     * @param $key string 键值
     * @return void
     */
    public function offsetUnset($id)
    {
        throw new CacheException('It is not permitted to set this property.');
    }
    
    /**
     * get cache instance by id
     *
     * @param string $id
     * @return \Tiny\Cache\:
     */
    public function __get($cacheId)
    {
        return $this->getStoragerById($cacheId);
    }
    
    /**
     * set cache instance
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($cacheId, $cacheInstance)
    {
        throw new CacheException('It is not permitted to set this property. ');
    }
}
?>