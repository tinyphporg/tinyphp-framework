<?php
/**
 *
 * @copyright (C), 2011-, King.
 * @name FileCache.php
 * @author King
 * @version 1.0
 * @Date: Sat Nov 12 23 16 52 CST 2011
 * @Description
 * @Class List
 *        1.File 文件缓存适配器
 * @History <author> <time> <version > <desc>
 *          King Mon Nov 14 00:08:21 CST 2011 Beta 1.0 第一次建立该文件
 *          King 2013-12-05 1.0 重新修订该文件
 *          King 2020年02月24日12:06:00 stable 1.0 审定稳定版本
 */
namespace Tiny\Cache\Storager;

use Tiny\Cache\CacheException;

/**
 * 文件缓存
 *
 * @package Tiny.Cache
 * @since Mon Nov 14 00 08 38 CST 2011
 * @final Mon Nov 14 00 08 38 CST 2011
 *        King 2020年02月24日16:54:00 stable 1.0 审定
 */
class File extends CacheStorager
{
    
    /**
     * 缓存的文件头长度
     *
     * @var int
     */
    const CACHE_HEADER_LENGTH = 10;
    
    /**
     * 缓存文件的扩展名
     *
     * @var string
     */
    const CACHE_FILE_EXT = '.txt'; 
   
    /**
     * 存储路径
     * 
     * @var string
     */
    protected $path;
    
    /**
     *  生命周期
     *  
     * @var integer
     */
    protected $ttl = 3600;
    
    /**
     * 初始化配置
     * @param array $config 配置数据
     * @throws CacheException
     */
    public function __construct(array $config = [])
    {
        $path = realpath($config['path']);
        if (!$path || !is_dir($path)) {
            throw new CacheException(sprintf('Class %s instantiation failed: the path %s does not exists', self::class, $path));
        }
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    
    /**
     * 设置缓存
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::set()
     */
    public function set(string $key, $value = null, int $ttl = 0)
    {
        if (!$key) {
            return false;
        }
        if ($ttl == 0) {
            $ttl = $this->ttl;
        }
        if ($ttl < 0) {
            return $this->delete($key);
        }
        
        //save to storage path
        $data = $this->packData($value, $ttl);
        return $this->saveTo($key, $data);
    }
    
    /**
     * 获取缓存数据 
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = null)
    {
        
        $content = $this->read($key);
        if (!$content) {
            return $default;
        }
        $value = $this->unpackData($content);
        if ($value === false) {
            unlink($this->getStoragePath($key));
        }
        return $value ?: $default;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has($key)
    {
        return $this->get(key) ? true : false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete($key)
    {
        $storagePath = $this->getStoragePath($key);
        if (!is_file($storagePath)) {
            return true;
        }
        return unlink($storagePath);
    }

    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::clear()
     */
    public function clear()
    {
        $fpaths = scandir($this->path);
        foreach ($fpaths as $fpath) {
            if (self::CACHE_FILE_EXT != substr($fpath, -4)) {
                continue;
            }
            unlink($this->path . $fpath);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::getMultiple()
     */
    public function getMultiple(array $keys, $default = null) 
    {
        $result = [];
        foreach($keys as $key) {
            $result[$key] = $this->get($key, $default);   
        }
        return $result;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::setMultiple()
     */
    public function setMultiple(array $values, int $ttl = 0)
    {
        $result = [];
        foreach ($values as $key => $value) {
            $result[$key] = $this->set($key, $value, $ttl);
        }
        return $result;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Cache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->delete($key);
        }
        return $result;
    }
    
    /**
     *  格式化存储数据
     *
     * @param mixed  $value 存储数据
     * @param int $ttl 存储生命周期
     * @return string
     */
    protected function packData($value, int $ttl)
    {
        return strval(time() + $ttl) . serialize($value);
    }
    
    
    /**
     * 解包数据
     * 
     * @param string $content
     * @return string|mixed
     */
    protected function unpackData(string $content)
    {
        $headerExpire = (int)substr($content, 0, self::CACHE_HEADER_LENGTH);
        if ($headerExpire < time()) {
            return false;
        }
        $content = substr($content, self::CACHE_HEADER_LENGTH);
        return unserialize($content);
    }
    
    /**
     * 读取文件数据
     * 
     * @param string $key 缓存KEY
     * @return string
     */
    protected function read(string $key)
    {
        $storagePath = $this->getStoragePath($key);
        if (!is_file($storagePath)) {
            return '';
        }
        if (!$fh = fopen($storagePath, 'r')) {
            unlink($storagePath);
            return '';
        }
        flock($fh, LOCK_SH);
        $fsize = filesize($storagePath);
        if ($fsize) {
            $content = fread($fh, $fsize);
        }
        flock($fh, LOCK_UN);
        fclose($fh);
        return $content;
    }
    /**
     * 写入文件
     *
     * @param $filename string 文件路径
     * @param string string 写入的字符串
     * @return bool
     */
    protected function saveTo(string $key, string $data)
    {
        $storagePath = $this->getStoragePath($key);
        return file_put_contents($storagePath, $data, LOCK_EX);
    }

    /**
     * 获取文件缓存路径
     *
     * @param $key string 键
     * @return string
     */
    protected function getStoragePath($key)
    {
        return $this->path . md5($key) . self::CACHE_FILE_EXT;
    }
}