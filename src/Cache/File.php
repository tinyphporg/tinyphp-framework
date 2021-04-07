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
 *          King 2020年02月24日12:06:00 stable 1.0.01 审定稳定版本
 */
namespace Tiny\Cache;

/**
 * 文件缓存
 *
 * @package Tiny.Cache
 * @since Mon Nov 14 00 08 38 CST 2011
 * @final Mon Nov 14 00 08 38 CST 2011
 *        King 2020年02月24日16:54:00 stable 1.0.01 审定
 */
class File implements ICache, \ArrayAccess
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
     * 默认的服务器缓存策略
     *
     * @var array
     */
    protected $_policy = [
        'path' => NULL,
        'lifetime' => 3600
    ];

    /**
     * 初始化路径
     *
     * @param array $policy
     *        代理的策略数组
     * @return void
     *
     */
    public function __construct(array $policy = [])
    {
        $policy = array_merge($this->_policy, $policy);
        $path = realpath($policy['path']);
        if (!$path || !is_dir($path))
        {
            throw new CacheException('Cache.File实例化失败：' . $policy['path'] . '不是一个已存在的目录');
        }
        $policy['path'] = $path . DIRECTORY_SEPARATOR;
        $this->_policy = $policy;
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
    public function set($key, $value = NULL, $life = 0)
    {
        if (!$key)
        {
            return FALSE;
        }

        if (is_array($key))
        {
            $life = (int)$value;
        }

        if ($life <= 0)
        {
            $life = $this->_policy['lifetime'];
        }

        if (!is_array($key))
        {
            return $this->_set($key, $value, $life);
        }
        foreach ($key as $k => $v)
        {
            $this->_set($k, $v, $life);
        }
        return TRUE;
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
        if (!is_array($key))
        {
            return $this->_get($key);
        }
        $ret = [];
        foreach ($key as $k)
        {
            $ret[$k] = $this->_get($k);
        }
        return $ret;
    }

    /**
     * 判断缓存是否存在
     *
     * @param string $key
     *        键
     * @return bool
     */
    public function exists($key)
    {
        if (!$key)
        {
            return FALSE;
        }
        return $this->_get($key) ? TRUE : FALSE;
    }

    /**
     * 移除缓存
     *
     * @param string $key
     *        缓存的键 $key为array时 可以批量删除
     * @return bool
     */
    public function remove($key)
    {
        if (NULL == trim($key))
        {
            return FALSE;
        }
        $filepath = $this->_getFilePath($key);
        if (!is_file($filepath))
        {
            return FALSE;
        }
        return unlink($filepath);
    }

    /**
     * 清理所有缓存
     *
     * @param
     *        void
     * @return void
     */
    public function clean()
    {
        $path = $this->_policy['path'];
        $fpaths = scandir($path);
        foreach ($fpaths as $fpath)
        {
            if (self::CACHE_FILE_EXT != substr($fpath, -4))
            {
                continue;
            }
            unlink($path . DIRECTORY_SEPARATOR . $fpath);
        }
    }

    /**
     * 数组接口之设置
     *
     * @param $key string
     *        键
     * @param $value mixed
     *        值
     * @return
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
        return (NULL == $this->get($key)) ? TRUE : FALSE;
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
     * 设置缓存变量
     *
     * @param $key string
     *        键
     * @param $value mixed
     *        值
     * @param $life int
     *        生命周期
     * @return bool
     */
    protected function _set($key, $value, $life)
    {
        $header = time() + (int)$life;
        $content = serialize($value);
        $data = $header . $content;
        $fpath = $this->_getCachePath($key);
        return $this->_writeFile($fpath, $data);
    }

    /**
     * 获取缓存变量
     *
     * @param $key string
     *        || array 为数组时可一次获取多个变量
     * @return bool;
     */
    protected function _get($key)
    {
        $fpath = $this->_getCachePath($key);
        if (!is_file($fpath))
        {
            return NULL;
        }
        if (!$fh = fopen($fpath, 'r'))
        {
            return NULL;
        }

        flock($fh, LOCK_SH);
        $fsize = filesize($fpath);
        if ($fsize)
        {
            $data = fread($fh, $fsize);
        }

        flock($fh, LOCK_UN);
        fclose($fh);
        $currentTime = time();
        $header = (int)substr($data, 0, self::CACHE_HEADER_LENGTH);
        if ($header < $currentTime)
        {
            unlink($fpath);
            return NULL;
        }
        $content = substr($data, self::CACHE_HEADER_LENGTH);
        $value = unserialize($content);
        return $value;
    }

    /**
     * 获取文件缓存路径
     *
     * @param $key string
     *        键
     * @return string
     */
    protected function _getCachePath($key)
    {
        return $this->_policy['path'] . md5($key) . self::CACHE_FILE_EXT;
    }

    /**
     * 写入文件
     *
     * @param $filename string
     *        文件路径
     * @param $string string
     *        写入的字符串
     * @return bool
     */
    protected function _writeFile($fpath, $data)
    {
        return file_put_contents($fpath, $data, LOCK_EX);
    }
}