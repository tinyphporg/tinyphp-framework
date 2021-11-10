<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Param.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月12日下午10:35:18
 * @Class List
 * @Function List
 * @History King 2017年3月12日下午10:35:18 0 第一次建立该文件
 *          King 2017年3月12日下午10:35:18 1 上午修改
 */
namespace Tiny\MVC\Request\Param;

use Tiny\Filter\IFilter;
use Tiny\Filter\Filter;
use Tiny\Tiny;

/**
 * 请求参数实例
 *
 * @package Tiny.Application.Request.Param
 *
 * @since 2017年3月12日下午10:35:54
 * @final 2017年3月12日下午10:35:54
 */
class Param implements \ArrayAccess, \Iterator, \Countable
{

    /**
     * 存储数据的数组
     *
     * @var array
     */
    protected $_data;

    /**
     * 过滤器
     *
     * @var \Tiny\Filter\IFilter
     */
    protected $_filter;

    /**
     * 是否默认开启过滤防止攻击
     *
     * @var bool
     */
    protected $_isFiltered;

    /**
     * 构造函数
     *
     * @param array $data
     *        数据
     * @param IFilter $filter
     *        过滤器实例
     * @return void
     */
    public function __construct(array & $data, $isFiltered = TRUE)
    {
        $this->_data = & $data;
        $this->_isFiltered = (bool)$isFiltered;
    }

    /**
     * 获取键
     *
     * @param string $offset
     * @return mixed
     *
     */
    public function get($offset = NULL, $isFormat = TRUE)
    {
        $data = (NULL === $offset) ? $this->_data : $this->_data[$offset];
        return (bool)$isFormat ? $this->_formatData($data) : $data;
    }

    /**
     * ArrayAccess get
     *
     * @param string $offset
     *        键
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $data = $this->_data[$offset];
        return $this->_formatData($data);
    }

    /**
     * ArrayAccess set
     *
     * @param string $offset
     *        键
     * @param mixed $value;
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * ArrayAccess exists
     *
     * @param string $offset
     *        键
     * @return mixed
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * ArrayAccess unset
     *
     * @param string $offset
     *        键
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Iterator rewind
     *
     * @return mixed
     *
     */
    public function rewind()
    {
        return reset($this->_data);
    }

    /**
     * Iterator current
     *
     * @return mixed
     *
     */
    public function current()
    {
        $data = current($this->_data);
        return $this->_formatData($data);
    }

    /**
     * Iterator next
     *
     * @return mixed
     *
     */
    public function next()
    {
        return next($this->_data);
    }

    /**
     * Iterator key
     *
     * @return mixed
     *
     */
    public function key()
    {
        return key($this->_data);
    }

    /**
     * Iterator valid
     *
     * @return mixed
     *
     */
    public function valid()
    {
        return key($this->_data) !== NULL;
    }

    /**
     * countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * 合并数据
     *
     * @param array $data
     * @return void
     */
    public function merge(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
    }
    
    /**
     * key is required
     * @param string $key
     * @return  bool
     */
    public function isRequired($key)
    {
        return isset($this->_data[$key]);
    }
    /**
     * tostring
     *
     * @return string
     */
    public function __toString()
    {
        return var_export($this->_data, true);
    }

    /**
     * 魔法调用过滤器
     *
     * @param string $method
     *        函数名称
     * @param array $args
     *        参数数组
     * @return void|mixed
     */
    public function __call($method, $args)
    {
        $filter = $this->_getFilter();
        if (!$filter)
        {
            return $args;
        }
        if (!(strlen($method) > 6 && 'format' == substr($method, 0, 6)))
        {
            return $args;
        }
        if ($args)
        {
            $key = $args[0];
            $args[0] = $this->_data[$key];
        }
        else
        {
            $args[0] = $this->_data;
        }
        return call_user_func_array([
            $filter,
            $method
        ], $args);
    }

    /**
     * 过滤数据
     *
     * @param array $data
     *        数据
     */
    protected function _formatData($data)
    {
        return $this->_isFiltered ? $this->_getFilter()->formatWeb($data) : $data;
    }

    /**
     * 获取过滤器
     *
     * @return Filter
     */
    protected function _getFilter()
    {
        if (!$this->_filter)
        {
            $this->_filter = Tiny::getApplication()->getFilter();
        }
        return $this->_filter;
    }
}
?>