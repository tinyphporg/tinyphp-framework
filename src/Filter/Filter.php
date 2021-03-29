<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Filter.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月9日下午9:21:05
 * @Class List
 * @Function List
 * @History King 2017年3月9日下午9:21:05 0 第一次建立该文件
 *          King 2017年3月9日下午9:21:05 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Filter;

use Tiny\MVC\Request\Base as Request;
use Tiny\MVC\Response\Base as Response;

/**
 *
 * @package Tiny.Filter
 * @since King 2017年3月9日下午9:21:05
 * @final King 2017年3月9日下午9:21:05
 *
 */
class Filter implements IFilter
{

    /**
     * 获取单例实例
     *
     * @var Filter
     */
    protected static $_instance;

    /**
     * 过滤器集合
     *
     * @var array
     */
    protected $_filters = [];

    /**
     * 获取单例实例
     *
     * @return Filter
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
     * 添加过滤器
     *
     * @param IFilter $filter
     * @return void
     */
    public function addFilter($filterName)
    {
        if (!in_array('Tiny\Filter\IFilter', class_implements($filterName)))
        {
            return;
        }
        if ($this->_filters[$filterName])
        {
            return;
        }
        $filter = new $filterName();
        $this->_filters[$filterName] = $filter;
    }

    /**
     * 过滤
     *
     * @param Request $req
     *        请求实例
     * @param Response $res
     *        响应实例
     * @return void
     */
    public function doFilter(Request $req, Response $res)
    {
        foreach ($this->_filters as $filter)
        {
            $filter->doFilter($req, $res);
        }
    }

    /**
     * 格式化成json
     *
     * @param int $status
     *        状态码
     * @param mixed $msg
     *        消息文本
     * @param mixed $data
     *        附带数据
     * @return array
     */
    public function formatJSON($status, $msg, $data)
    {
        return [
            'status' => $status,
            'msg' => $msg,
            'data' => $data
        ];
    }

    /**
     * 格式化int
     *
     * @param int $int
     *        整数
     * @param int $min
     *        最小值
     * @param int $max
     *        最大值
     * @return int
     */
    public function formatInt($int, int $min = NULL, int $max = NULL): int
    {
        $int = (int)$int;
        if ($min !== NULL && $int < $min)
        {
            $int = $min;
        }
        if ($max !== NULL && $int > $max)
        {
            $int = $max;
        }
        return $int;
    }

    /**
     * 格式化成string
     *
     * @param string $str
     *        文本字符串
     * @param string $default
     *        默认字符串
     */
    public function formatString($str, $default = NULL)
    {
        return (string)$str ?: $default;
    }

    /**
     * 全部小写
     *
     * @param string $str
     *        字符串
     * @param string $default
     *        默认字符串
     * @return string
     */
    public function formatLower($str, $default = NULL)
    {
        $str = strtolower($str);
        return $this->formatString($str, $default);
    }

    /**
     * 全部大写
     *
     * @param string $str
     *        字符串
     * @param string $default
     *        默认字符串
     * @return string
     */
    public function formatUpper($str, $default = NULL)
    {
        $str = strtoupper($str);
        return $this->formatString($str, $default);
    }

    /**
     * 防注入和XSS攻击
     *
     * @param mixed $data
     *        数据
     * @return mixed
     */
    public function formatWeb($data)
    {
        if (is_array($data))
        {
            $ndata = [];
            foreach ($data as $k => $v)
            {
                $ndata[$k] = $this->formatWeb($v);
            }
            return $ndata;
        }
        $data = htmlspecialchars($data);
        $data = preg_replace('/^(select|insert|and|or|create|update|delete|alter|count|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile)/i', '', $data);
        $data = addslashes($data);
        return $data;
    }

    /**
     * 去除html标签
     *
     * @param string $str
     *        字符串
     * @param string $tags
     *        标签数
     * @return string
     */
    public function formatStripTags($str, $tags = NULL)
    {
        return strip_tags($str, $tags);
    }

    /**
     * 去除空格
     *
     * @param string $str
     *        字符串
     * @return string
     */
    public function formatTrim($str)
    {
        return trim($str);
    }

    /**
     * 魔法构造函数  format格式化
     *
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        if (!(strlen($method) > 6 && 'format' == substr($method, 0, 6)))
        {
            return $args;
        }

        foreach ($this->_filters as $filter)
        {
            if (!method_exists($filter, $method))
            {
                continue;
            }
            return call_user_func_array([
                $filter,
                $method
            ], $args);
        }
    }

    /**
     * 限制单例
     *
     * @return void
     */
    protected function __construct()
    {
    }
}
?>