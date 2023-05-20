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
     * 缓存的命名空间
     *
     * @var string
     */
    protected  $namespace;
    
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
        $this->data = $this->readFrom($this->id);
        $this->namespace = isset($config['namespace']) ? '.singlecache.' . $config['namespace']  : '.singlecache';    
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has($key) 
    {
        $dataItem = $this->readItem($key);
        return is_array($dataItem);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = null)
    {
        $dataItem = $this->readItem($key);
        return is_array($dataItem) ? $dataItem['value'] : $default;
    }

    /**
     * 
     * {@inheritDoc}
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
     * {@inheritDoc}
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
        $this->updateData(true);
    }
    
    /**
     * 
     * {@inheritDoc}
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
        $this->updateData(true);
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
        $this->updateData(true);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete(string $key)
    {
        if (!key_exists($key, $this->data)) {
            return;
        } 
       unset($this->data[$key]);
       $this->updateData(true);
       return true;
        
    }
    
    /**
     * 
     * {@inheritDoc}
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
            $this->updateData($hasUpdated);
        }
    }
    
    /**
     * 析构函数自动保存
     */
    public function __destruct()
    {
        $this->saveTo();
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
        $this->exprieTime = (int)$data['exprie'];
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
            $this->updateData(true);
            return false;
        }
        return $dataItem;
    }
    
    /**
     * 读取所有的数据item
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
        foreach($data as $key => $item) {
            if (!is_array($item) || !key_exists('exprie', $item) || !key_exists('value', $item) || $item['exprie'] < $currentTime) {
                $hasUpdated = true;
                continue;
            }
            $rdata[$key] = $item;
        }
        $this->updateData($hasUpdated);
        return $rdata;
    }
    
    
    /**
     * 
     * @param bool $hasUpdated 是否已经更新
     */
    protected function updateData($hasUpdated = false) {
        if ($hasUpdated) {
            $this->hasUpdated = $hasUpdated;
        }
        if (!$hasUpdated) {
            return;
        }
        $currentTime = time();
        foreach($this->data as $key => $item) {
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
        
        //
        $expriation = time() + $this->timeout;
        $data = [
            'exprie' => $expriation,
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
        return $this->path . md5($this->id) . $this->namespace . '.php';
    }
    
}
?>