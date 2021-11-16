<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name JSON.php
 * @author King
 * @version Beta 1.0
 * @Date Mon Jan 02 13:18:37 CST 2012
 * @Description JSON格式的编码与解码类
 * @Class List
 *        1. JSON JSON编码和解码工具类
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Mon Jan 02 13:18:37 CST 2012 Beta 1.0 第一次建立该文件
 *          King 2020年02月23日上午00:12:00 stable 1.0 审定稳定版本
 *
 */
namespace Tiny\String;

/**
 * JSON编码类
 *
 * @package Tiny.String
 * @since Mon Jan 02 14:43:27 CST 2012
 * @final Mon Jan 02 14:43:27 CST 2012
 *        2020年02月23日上午00:12:00 stable 1.0 审定稳定版本
 */
class JSON
{

    /**
     * JSON编码 目前只接受UTF-8编码的数据
     *
     * @param $obj 接受UTF-8编码的任何数据类型,资源类型除外.
     * @return string
     */
    public static function encode($value, $options = NULL, $depth = NULL): string
    {
        $ret = json_encode($value, $options, $depth);
        self::_checkException();
        return $ret;
    }

    /**
     * JSON解码
     *
     * @param string $str
     *        待解码的JSON字符串
     * @param bool $assoc
     *        返回为array或者是object 默认为true 返回数组.
     * @return array || object
     */
    public static function decode($jsonStr, $assoc = TRUE, $options = NULL, $depth = NULL)
    {
        $ret = json_decode($jsonStr, $assoc, $depth, $options);
        self::_checkException();
        return $ret;
    }

    /**
     * 检测返回JSON解析的最后一个错误 如果有的话
     */
    protected static function _checkExcption()
    {
        $errno = json_last_error();
        if ($errno == JSON_ERROR_NONE)
        {
            return;
        }
        $errmsg = json_last_error_msg();
        new \JsonException($errmsg, E_NOTICE);
    }
}
?>