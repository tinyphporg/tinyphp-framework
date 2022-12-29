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
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Filter;

use Tiny\MVC\Request\Request;
use Tiny\MVC\Response\Response;

/**
 *
 * @package Tiny.Filter
 * @since King 2017年3月9日下午9:21:05
 * @final King 2017年3月9日下午9:21:05
 *       
 */
class Filter implements FilterInterface
{
    
    /**
     * 过滤器集合
     *
     * @var array
     */
    protected $filters = [];
    
    /**
     * 添加过滤器
     *
     * @param string $filterClass 过滤器类名
     * @return void
     */
    public function addFilter($filterClass)
    {
        if (!in_array(FilterInterface::class, class_implements($filterClass))) {
            return;
        }
        
        if ($this->filters[$filterClass]) {
            return;
        }
        $filterInstance = new $filterClass();
        $this->filters[$filterClass] = $filterInstance;
    }
    
    /**
     * 过滤器
     *
     * @param Request $req
     * @param Response $res
     */
    public function filter(Request $request, Response $response)
    {
        foreach ($this->filters as $filter) {
            $filter->filter($request, $response);
        }
    }
    
    /**
     * 格式化成json
     *
     * @param int $status 状态码
     * @param mixed $msg 消息文本
     * @param mixed $data 附带数据
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
     * @param int $int 整数
     * @param int $min 最小值
     * @param int $max 最大值
     * @return int
     */
    public function formatInt($int, int $min = null, int $max = null): int
    {
        $int = (int)$int;
        if ($min !== null && $int < $min) {
            $int = $min;
        }
        if ($max !== null && $int > $max) {
            $int = $max;
        }
        return $int;
    }
    
    /**
     * 格式化成string
     *
     * @param string $str 文本字符串
     * @param string $default 默认字符串
     */
    public function formatString($str, $default = null)
    {
        return (string)$str ?: $default;
    }
    
    /**
     * 全部小写
     *
     * @param string $str 字符串
     * @param string $default 默认字符串
     * @return string
     */
    public function formatLower($str, $default = null)
    {
        $str = strtolower($str);
        return $this->formatString($str, $default);
    }
    
    /**
     * 全部大写
     *
     * @param string $str 字符串
     * @param string $default 默认字符串
     * @return string
     */
    public function formatUpper($str, $default = null)
    {
        $str = strtoupper($str);
        return $this->formatString($str, $default);
    }
    
    /**
     * 防注入和XSS攻击
     *
     * @param mixed $data 数据
     * @return mixed
     */
    public function formatWeb($data)
    {
        if (is_array($data)) {
            $ndata = [];
            foreach ($data as $k => $v) {
                $ndata[$k] = $this->formatWeb($v);
            }
            return $ndata;
        }
        $data = htmlspecialchars($data);
        $data = preg_replace(
            '/^\s*(select|insert|and|or|create|update|delete|alter|count|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile)/i',
            '', $data);
        $data = addslashes($data);
        return $data;
    }
    
    /**
     * 去除html标签
     *
     * @param string $str 字符串
     * @param string $tags 标签数
     * @return string
     */
    public function formatStripTags($str, $tags = null)
    {
        return strip_tags($str, $tags);
    }
    
    /**
     * 去除空格
     *
     * @param string $str 字符串
     * @return string
     */
    public function formatTrim($str)
    {
        return trim($str);
    }
    
    /**
     * 魔法构造函数 format格式化
     *
     * @param string $method
     * @param array $params
     */
    public function __call(string $method, array $params)
    {
        if (!(strlen($method) > 6 && 'format' == substr($method, 0, 6))) {
            return;
        }
        
        foreach ($this->filters as $filter) {
            if (!method_exists($filter, $method)) {
                continue;
            }
            return call_user_func_array([
                $filter,
                $method
            ], $params);
        }
    }
}
?>