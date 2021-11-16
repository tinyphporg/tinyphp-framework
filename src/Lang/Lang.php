<?php
/**
 *
 * @copyright (C), 2011-, King.
 * @name Lang.php
 * @author King
 * @version Beta 1.0
 * @Date: Sat May 12 18:23:40 CST 2012
 * @Description
 * @Class List
 *        1. 语言类
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Sat May 12 18:23:40 CST 2012 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Lang;

use Tiny\Config\Configuration;
use Tiny\Cache\ICache;

/**
 * 语言类
 *
 * @package Tiny.Lang
 * @since Sat May 12 18:35:34 CST 2012
 * @final Sat May 12 18:35:34 CST 2012
 *
 */
class Lang implements \ArrayAccess
{

    /**
     * 单例
     *
     * @var Lang
     */
    protected static $_instance;

    /**
     * 获取单例
     *
     * @return Lang
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
     * 语言数据文件目录
     *
     * @var string
     */
    protected $_langPath = NULL;

    /**
     * 语言种类
     *
     * @var string
     */
    protected $_locale = NULL;

    /**
     * 语言数据配置实例
     *
     * @var Configuration
     */
    protected $_config = [];
    
    /**
     * 语言缓存实例
     * @var \Tiny\Cache\ICache
     */
    protected $_cache;
    
    /**
     * 缓存策略
     * @var array
     */
    protected $_cachePolicy = [
        'dataId'
    ];
    /**
     * 
     * @var array
     */
    protected $cachePolicy = [];

    /**
     * 设置语言数据文件夹路径
     *
     * @param string $path
     *        文件夹路径
     * @return bool
     */
    public function setPath($path)
    {
        $this->_langPath = $path;
        return $this;
    }

    /**
     * 设置语言种类
     *
     * @param string $locale
     *        语言名称
     * @return Lang
     *
     */
    public function setLocale($locale)
    {
        $this->_locale = $locale;
        return $this;
    }
    
    /**
     * 设置语言包初始数据
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->_getLangConfig()->setData($data);
    }
    
    /**
     * 获取语言包数据
     * @return array
     */
    public function getData()
    {
        return $this->_getLangConfig()->get();
    }
    
    /**
     * 执行翻译
     *
     * @param string $code
     *        字符串代码
     * @return string
     */
    public function translate($code, ...$params)
    {
        $config = $this->_getLangConfig();
        $string = $config[$this->_locale . '.' . $code];
        if ($params)
        {
            $string = vsprintf($string, $params);
        }
        return $string;
    }

    /**
     * ArrayAccess 获取某个语言编码的值
     *
     * @param string $code
     *        语言码
     * @return string
     */
    public function offsetGet($code)
    {
        return $this->translate($code);
    }

    /**
     * ArrayAccess 设置某个语言编码的值 不可用
     *
     * @param string $code
     *        语言码
     * @param string $val
     *        翻译后的值
     * @return bool
     */
    public function offsetSet($code, $val)
    {
        return FALSE;
    }

    /**
     * ArrayAccess 去掉某个语言码内容 不可用
     *
     * @param string $code
     *        语言码
     * @return bool
     */
    public function offsetUnset($code)
    {
        return FALSE;
    }

    /**
     * ArrayAcess 是否存在该语言包代码
     *
     * @param string $code
     *        语言码
     * @return bool
     */
    public function offsetExists($code)
    {
        return (bool)$this->offsetGet($code);
    }

    /**
     * 获取语言数据配置实例
     *
     * @param string $code
     *        语言代码
     * @return Configuration
     */
    protected function _getLangConfig()
    {
        if (!$this->_config)
        {
            $this->_config = new Configuration($this->_langPath);
        }
        return $this->_config;
    }
}
?>