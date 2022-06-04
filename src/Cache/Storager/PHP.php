<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name PHP.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月14日上午9:33:25
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月14日上午9:33:25 2017年3月8日下午4:20:28 0 第一次建立该文件
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
class PHP extends CacheStorager
{
    
    /**
     * 存储路径
     *
     * @var string
     */
    protected $path;
    
    /**
     * 生命周期
     *
     * @var integer
     */
    protected $ttl = 3600;
    
    /**
     * 初始化配置
     *
     * @param array $config 配置数据
     * @throws CacheException
     */
    public function __construct(array $config = [])
    {
        $path = (string)$config['path'];
        if (!$path) {
            throw new CacheException(sprintf('Class %s instantiation failed: %s does not exists', self::class, $path));
        }
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    
    /**
     * 设置缓存
     *
     * {@inheritdoc}
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
        
        // save to storage path
        return $this->saveTo($key, $value, $ttl);
    }
    
    /**
     * 获取缓存数据
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::get()
     */
    public function get(string $key, $default = null)
    {
        return $this->readFrom($key) ?: $default;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::has()
     */
    public function has($key)
    {
        return $this->get(key) ? true : false;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::delete()
     */
    public function delete($key)
    {
        $storagePath = $this->getStoragePath($key);
        if (!is_file($storagePath)) {
            return false;
        }
        return unlink($storagePath);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::clear()
     */
    public function clear()
    {
        $fpaths = scandir($this->path);
        foreach ($fpaths as $fpath) {
            if (strlen($fpath) <= 10 || '.cache.php' != substr($fpath, -10)) {
                continue;
            }
            unlink($this->path . $fpath);
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Cache\CacheInterface::getMultiple()
     */
    public function getMultiple(array $keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }
    
    /**
     *
     * {@inheritdoc}
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
     * {@inheritdoc}
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
        return $this->path . md5($key) . '.cache.php';
    }
}
?>