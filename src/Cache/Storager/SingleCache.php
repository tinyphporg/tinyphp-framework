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
     * 单例存储库的ID
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
     * gc时间
     * 
     * @var int
     */
    protected $timeout;
    
    /**
     * 是否更新过缓存
     * 
     * @var boolean
     */
    protected $hasUpdated = false;
    
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
        $this->id = md5($config['id'] ?: get_included_files()[0]);
        $this->ttl = (int)$config['ttl'] ?: 3600; //缓存时间
        $this->timeout = (int)$config['timeout'] ?: 60; // 回收清理时间
        $this->data = (array)$this->readFrom($this->id);
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
            $this->saveTo();
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
           $this->delete($key);
        }
    }
    
    /**
     * 析构函数自动保存
     */
    public function __destruct()
    {
        $this->saveTo();
    }
    
    protected function updateTo() {
        
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
        if (!is_array($data) || !key_exists('exprieTime', $data) || !key_exists('data', $data)) {
            return [];
        }
        
        $this->exprieTime = (int)$data['exprieTime'];
        $currentTime = time();
        $rdata = [];
        foreach($data as $key => $item) {
            if (!is_array($item) || !key_exists('exprieTime', $item) || !key_exists('value', $item)) {
                continue;
            }
            if ($item['exprieTime'] < $currentTime) {
                continue;
            }
            $rdata[$key] = $item;
        }
        return $rdata;
    }
    
    protected function readNode($key) 
    {
        if (!key_exists($key, $this->data)) {
            return;
        }
        $node = $this->data[$key];
        if (!is_array($node) || !key_exists('', $search)) {
            
        }
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
        
        //
        $expriation = time() + $this->timeout;
        $data = [
            'exprieTime' => $expriation,
            'data' => $this->data
        ];
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
        return $this->path . md5($this->id) . '.singlecache.php';
    }
    
}
?>