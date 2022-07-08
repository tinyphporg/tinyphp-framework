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
namespace Tiny\Cache\Storager;

use Tiny\Cache\CacheException;

/**
* 简单的延后缓存类
*  
* @package Tiny.Cache
* @since 2022年5月17日下午2:15:47
* @final 2022年5月17日下午2:15:47
*/
class SingleCache extends CacheStorager
{
    /**
     * 存储路径
     *
     * @var string
     */
    protected $path;
    /**
     * 缓存KEY
     *
     * @var string
     */
    protected $key;
    
    /**
     *
     * @var array
     */
    protected $data = [];
    
    /**
     * 缓存生命周期
     * 
     * @var integer
     */
    protected $ttl;
    
    /**
     * 是否更新过缓存
     * 
     * @var boolean
     */
    protected $isUpdated = false;
    
    /**
     * 构造函数
     * 
     * @param string $cachePoolId
     * @param CacheStorager $cacheStorager
     */
    public function __construct(array $config = [])
    {
        $path = (string)$config['path'];
        if (!$path) {
            throw new CacheException(sprintf('Class %s instantiation failed: %s does not exists', self::class, $path));
        }
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->key = (string)$config['key'] ?: get_included_files()[0];
        $this->ttl = (int)$config['ttl'] ?: 60;
        $this->data = (array)$this->readFrom($this->key);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has($key) 
    {
        return key_exists($key, $this->data);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = NULL)
    {
        if (key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::getMultiple()
     */
    public function getMultiple(array $keys, $default = NULL)
    {
        if (!$keys) {
            return $this->data;
        }
        $res = [];
        foreach ($keys as $key) {
            if (key_exists($key, $this->data)) {
                $res[$key] = $this->data[$key];
            }
        }
        return $res;
    }
    
    /**
     * ttl无效
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::set()
     */
    public function set($key, $value, int $ttl = 0)
    {
        $this->isUpdated = true;
        $this->data[$key] = $value;
        if ($ttl) {
            $this->saveTo($this->key, $this->data, $this->ttl);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::setMultiple()
     */
    public function setMultiple(array $values, int $ttl = 0)
    {
        $this->isUpdated = true;
        foreach ((array)$values as $key => $value) {
            $this->data[$key] = $value;
        }
        if ($ttl) {
            $this->saveTo($this->key, $this->data, $this->ttl);
        }
        return true;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::clear()
     */
    public function clear()
    {
        $this->data = [];
        $this->isUpdated;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete(string $key)
    {
        if (key_exists($key, $this->data)) {
            unset($this->data[$key]);
            $this->isUpdated = true;
            return true;
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            if (key_exists($key, $this->data)) {
                unset($this->data[$key]);
                $this->isUpdated  = true;
            }
        }
    }
    
    /**
     * 析构函数自动保存
     */
    public function __destruct()
    {
        if ($this->isUpdated) {
            $this->saveTo($this->key, $this->data, $this->ttl);
        }
        
    }
    
    /**
     * 读取文件数据
     *
     * @param string $key 缓存KEY
     * @return string
     */
    protected function readFrom(string $key)
    {
        $storagePath = $this->getStoragePath($key);
        if (!((extension_loaded('opcache') && opcache_is_script_cached($storagePath)) || is_file($storagePath))) {
            return;
        }
        $data = include ($storagePath);
        if (!$data || !is_array($data)) {
            return;
        }
        if (!isset($data['expriation']) || !isset($data['value'])) {
            return;
        }
        if (time() > intval($data['expriation'])) {
            return;
        }
        return $data['value'];
    }
    
    /**
     * 写入文件
     *
     * @param $filename string 文件路径
     * @param string string 写入的字符串
     * @return bool
     */
    protected function saveTo(string $key, $value, int $ttl)
    {
        $expriation = time() + $ttl;
        $data = [
            'expriation' => $expriation,
            'value' => $value
        ];
        $content = "<?php\n return " . var_export($data, true) . ";\n?>";
        $storagePath = $this->getStoragePath($key);
        return file_put_contents($storagePath, $content, LOCK_EX);
    }
    
    /**
     * 获取文件缓存路径
     *
     * @param $key string 键
     * @return string
     */
    protected function getStoragePath($key)
    {
        return $this->path . md5($key) . '.singlecache.php';
    }
    
}
?>