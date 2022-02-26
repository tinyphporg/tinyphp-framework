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
namespace Tiny\Runtime\Param;

use Tiny\Filter\Filter;
use Tiny\Tiny;
use Tiny\Filter\FilterInterface;

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
    protected $data;
    
    /**
     * 过滤器
     *
     * @var FilterInterface
     */
    protected $filter;
    
    /**
     * 是否默认开启过滤防止攻击
     *
     * @var bool
     */
    protected $isFiltered;
    
    /**
     * 构造函数
     *
     * @param array $data 数据
     * @param FilterInterface $filter 过滤器实例
     * @return void
     */
    public function __construct(array $data = [], $isFiltered = true)
    {
        $this->data = $data;
        $this->isFiltered = (bool)$isFiltered;
    }
    
    /**
     * 获取键
     *
     * @param string $offset
     * @return mixed
     *
     */
    public function get($offset = null, $isFormat = true)
    {
        $data = (null === $offset) ? $this->data : $this->data[$offset];
        return (bool)$isFormat ? $this->formatData($data) : $data;
    }
    
    /**
     * ArrayAccess get
     *
     * @param string $offset 键
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $data = $this->data[$offset];
        return $this->formatData($data);
    }
    
    /**
     * ArrayAccess set
     *
     * @param string $offset 键
     * @param mixed $value;
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }
    
    /**
     * ArrayAccess exists
     *
     * @param string $offset 键
     * @return mixed
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }
    
    /**
     * ArrayAccess unset
     *
     * @param string $offset 键
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::rewind()
     */
    public function rewind()
    {
        return reset($this->data);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::current()
     */
    public function current()
    {
        $data = current($this->data);
        return $this->formatData($data);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::next()
     */
    public function next()
    {
        return next($this->data);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::key()
     */
    public function key()
    {
        return key($this->data);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::valid()
     */
    public function valid()
    {
        return key($this->data) !== null;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Countable::count()
     */
    public function count()
    {
        return count($this->data);
    }
    
    /**
     * 合并数据
     *
     * @param array $data
     * @return void
     */
    public function merge(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }
    
    /**
     * key is required
     *
     * @param string $key
     * @return bool
     */
    public function isRequired($key)
    {
        return isset($this->data[$key]);
    }
    
    /**
     * tostring
     *
     * @return string
     */
    public function __toString()
    {
        return var_export($this->data, true);
    }
    
    /**
     * 魔法调用过滤器
     *
     * @param string $method 函数名称
     * @param array $args 参数数组
     * @return void|mixed
     */
    public function __call($method, $args)
    {
        $filter = $this->getFilter();
        if (!$filter) {
            return $args;
        }
        if (!(strlen($method) > 6 && 'format' == substr($method, 0, 6))) {
            return $args;
        }
        if ($args) {
            $key = $args[0];
            $args[0] = $this->data[$key];
        } else {
            $args[0] = $this->data;
        }
        return call_user_func_array([
            $filter,
            $method
        ], $args);
    }
    
    /**
     * 过滤数据
     *
     * @param array $data 数据
     */
    protected function formatData($data)
    {
        return $this->isFiltered ? $this->getFilter()->formatWeb($data) : $data;
    }
    
    /**
     * 获取过滤器
     *
     * @return Filter
     */
    protected function getFilter()
    {
        if (!$this->filter) {
            $this->filter = Tiny::getApplication()->get(Filter::class);
        }
        return $this->filter;
    }
}
?>