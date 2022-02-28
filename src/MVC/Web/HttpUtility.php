<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name HttpUtility.php
 * @author King
 * @version Beta 1.0
 * @Date: Mon Dec 19 00:35 23 CST 2011
 * @Description 提供用于在处理 Web 请求时编码和解码 URL 的方法
 * @Class List
 *        1.HttpUtility 提供用于在处理 Web 请求时编码和解码 URL 的方法
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Mon Dec 19 00:35:23 CST 2011 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Web;

/**
 * 提供用于在处理 Web 请求时编码和解码 URL 的方法
 *
 * @package Tiny.MVC.Web
 * @since Mon Dec 19 00:36 17 CST 2011
 * @final Mon Dec 19 00:36 17 CST 2011
 */
class HttpUtility
{
    
    /**
     * 将字符串转换为HTML实体
     *
     * @param string $string 字符串
     * @param int $quoteStyle 是否转换双引号和单引号 默认只转换双引号
     * @param string $charset 默认编码为UTF-8
     * @return string
     */
    public static function htmlEncode($string, $quoteStyle = ENT_COMPAT, $charset = 'UTF-8')
    {
        return htmlentities($string, $quoteStyle, $charset);
    }
    
    /**
     * 将HTML实体转换为字符串
     *
     * @param $htmlEntities string 已经转码的HTML字符串
     * @param int $quoteStyle 是否转换双引号和单引号 默认只转换双引号
     * @param string $charset 默认编码为UTF-8
     * @return string
     */
    public static function htmlDecode($htmlEntities, $quoteStyle = ENT_COMPAT, $charset = 'UTF-8')
    {
        return html_entity_decode($htmlEntities, $quoteStyle, $charset);
    }
    
    /**
     * 解析URL QueryString字符串为数组
     *
     * @param string $string 字符串
     * @return array
     */
    public static function parseQueryString($string)
    {
        $array = [];
        parse_str($string, $array);
        return $array;
    }
    
    /**
     * 将数组转换为QueryString
     *
     * @param array $params 需要转换的 数组
     * @return string
     */
    public static function queryString(array $params)
    {
        return http_build_query($params);
    }
    
    /**
     * 对字符串进行Url编码
     *
     * @param string $string 字符串
     * @return string
     */
    public static function urlEncode($string)
    {
        return urlencode($string);
    }
    
    /**
     * 对字符串进行URL解码
     *
     * @param string $string 字符串
     * @return string
     */
    public static function urlDecode($string)
    {
        return urldecode($string);
    }
    
    /**
     * 对字符串进行Url编码
     *
     * @param string $string 字符串
     * @return string
     */
    public static function rawUrlEncode($string)
    {
        return rawurlencode($string);
    }
    
    /**
     * 对字符串进行URL解码
     *
     * @param string $string 字符串
     * @return string
     */
    public static function rawUrlDecode($string)
    {
        return rawurldecode($string);
    }
}
?>