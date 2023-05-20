<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name CacheItemPool.php
 * @author King
 * @version stable 2.0
 * @Date 2022年5月16日下午4:44:39
 * @Class List class
 * @Function List function_container
 * @History King 2022年5月16日下午4:44:39 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Runtime;

/**
 * 简单的延后缓存类
 *
 * @package Tiny.Cache
 * @since 2022年5月17日下午2:15:47
 * @final 2022年5月17日下午2:15:47
 */
class RuntimeCache implements \ArrayAccess
{
    
    /**
     * 存储路径
     *
     * @var string
     */
    protected $path;
    
    /**
     * 存储的空间ID
     *
     * @var string
     */
    protected $id;
    
    /**
     *
     * @var array
     */
    protected $data = [];
    
    /**
     * 缓存默认的生命周期
     *
     * @var integer
     */
    protected $ttl;
    
    /**
     * gc间隔时间
     *
     * @var int
     */
    protected $gcInterval;
    
    /**
     * gc过期时间
     *
     * @var int
     */
    protected $gcExprie;
    
    /**
     * 是否更新过缓存
     *
     * @var boolean
     */
    protected $hasUpdated = false;
    
    /**
     * 构造函数
     *
     * @param string $path runtime文件存储缓存路径
     * @param string $id 唯一ID
     * @param int $interval 扫描时间间隔
     * @param number $ttl 默认缓存生命周期
     */
    public function __construct(string $path, string $cacheId, int $interval = 60, $ttl = 3600)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->id = $cacheId ?: get_included_files()[0];
        $this->ttl = $ttl ?: 3600; // 缓存时间
        $this->gcInterval = $interval ?: 60; // 回收清理时间
        $this->data = $this->readFrom($this->id);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has($key)
    {
        $dataItem = $this->readItem($key);
        return is_array($dataItem);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = null)
    {
        $dataItem = $this->readItem($key);
        return is_array($dataItem) ? $dataItem['value'] : $default;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::getMultiple()
     */
    public function getMultiple(array $keys, $default = NULL)
    {
        if (empty($keys)) {
            return $this->formatItems($this->data);
        }
        
        $data = [];
        foreach ($keys as $key) {
            if (!key_exists($key, $this->data)) {
                continue;
            }
            $data[$key] = $this->data[$key];
        }
        return $this->formatItems($data);
    }
    
    /**
     * ttl无效
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::set()
     */
    public function set($key, $value, int $ttl = 0)
    {
        if ($ttl < 0) {
            return $this->delete($key);
        }
        $ttl = $ttl ?: $this->ttl;
        $exprie = time() + 60;
        $this->data[$key] = ['exprie' => $exprie, 'value' => $value];
        $this->gc(true);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::setMultiple()
     */
    public function setMultiple(array $values, int $ttl = 0)
    {
        if (empty($values)) {
            return;
        }
        
        // ttl <=0 时 即为删除
        if ($ttl < 0) {
            $keys = array_keys($values);
            return $this->deleteMultiple($keys);
        }
        $ttl = $ttl ?: $this->ttl;
        $exprie = time() + 60;
        foreach ($values as $key => $value) {
            $this->data[$key] = ['exprie' => $exprie, 'value' => $value];
        }
        $this->gc(true);
        return true;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::clear()
     */
    public function clear()
    {
        $this->data = [];
        $this->gc(true);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete(string $key)
    {
        if (!key_exists($key, $this->data)) {
            return;
        }
        unset($this->data[$key]);
        $this->gc(true);
        return true;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys)
    {
        $hasUpdated = false;
        foreach ($keys as $key) {
            if (!key_exists($key, $this->data)) {
                continue;
            }
            unset($this->data[$key]);
            $hasUpdated = true;
        }
        
        // 批量操作，只检测一次
        if ($hasUpdated) {
            $this->gc($hasUpdated);
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }
    
    /**
     * 析构函数自动保存
     */
    public function __destruct()
    {
        $this->gc();
    }
    
    /**
     * 读取文件数据
     *
     * @param string $id 缓存id
     * @return string
     */
    protected function readFrom(string $id)
    {
        $storagePath = $this->getStoragePath($id);
        if (!((extension_loaded('opcache') && opcache_is_script_cached($storagePath)) || is_file($storagePath))) {
            return [];
        }
        
        $data = include ($storagePath);
        if (!is_array($data) || !key_exists('exprie', $data) || !key_exists('data', $data)) {
            return [];
        }
        $this->gcExprie = (int)$data['exprie'];
        return $this->formatItems($data['data']);
    }
    
    /**
     * 读取data的item
     *
     * @param string $key
     * @return array|false
     */
    protected function readItem(string $key)
    {
        if (!key_exists($key, $this->data)) {
            return false;
        }
        
        $dataItem = $this->data[$key];
        if (!is_array($dataItem) || !key_exists('exprie', $dataItem) || time() > $dataItem['exprie'] || !key_exists('value', $dataItem)) {
            unset($this->data[$key]);
            $this->gc(true);
            return false;
        }
        return $dataItem;
    }
    
    /**
     * 格式化所有的数据item
     *
     * @param array $data 数据
     * @return array
     */
    protected function formatItems(array $data)
    {
        if (empty($data)) {
            return $data;
        }
        
        // 过滤不符合规范和过期的item
        $currentTime = time();
        $rdata = [];
        $hasUpdated = false;
        foreach ($data as $key => $item) {
            if (!is_array($item) || !key_exists('exprie', $item) || !key_exists('value', $item) || $item['exprie'] < $currentTime) {
                $hasUpdated = true;
                continue;
            }
            $rdata[$key] = $item;
        }
        $this->gc($hasUpdated);
        return $rdata;
    }
    
    /**
     * 更新数据
     *
     * @param bool $hasUpdated 是否已经更新
     */
    protected function gc($hasUpdated = false)
    {
        if ($hasUpdated) {
            $this->hasUpdated = $hasUpdated;
        }
        $currentTime = time();
        if (!$this->hasUpdated || $this->gcExprie > $currentTime) {
            return;
        }
        foreach ($this->data as $key => $item) {
            if (!is_array($item) || !key_exists('exprie', $item) || !key_exists('value', $item) || $item['exprie'] < $currentTime) {
                unset($this->data[$key]);
            }
        }
        $this->saveTo();
    }
    
    /**
     * 写入文件
     *
     * @param $filename string 文件路径
     * @param string string 写入的字符串
     * @return bool
     */
    protected function saveTo()
    {
        if (!$this->hasUpdated) {
            return;
        }
        
        $this->hasUpdated = false;
        $expriation = time() + $this->timeout;
        $data = ['exprie' => $expriation, 'data' => $this->data];
        $content = "<?php\n return " . var_export($data, true) . ";\n?>";
        $storagePath = $this->getStoragePath();
        return file_put_contents($storagePath, $content, LOCK_EX);
    }
    
    /**
     * 获取文件缓存路径
     *
     * @param $key string 键
     * @return string
     */
    protected function getStoragePath()
    {
        return $this->path . $this->id . '.runtimecache.php';
    }
}
?>