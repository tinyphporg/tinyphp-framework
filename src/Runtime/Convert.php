<?php
/**
 *
 * @copyright (C), 2011-, King
 * @name Convert.php
 * @author King
 * @version Beta 1.0 2013-4-1下午02:54:29
 *          stable 1.0.01 2020年02月19日下午15:44:00
 * @Description 类型转换相关
 * @Class List
 *        1.Convert 类型转换的工具类
 * @History <author> <time> <version > <desc>
 *          King 2013-4-1下午02:54:29 Beta 1.0 第一次建立该文件
 *          King 2020年02月19日下午15:44:00 stable 1.0 审定稳定版本
 */
namespace Tiny\Runtime;

/**
 * 将一个基本数据类型转换为另一个基本数据类型。
 *
 * @package Tiny.Runtime
 * @since : 2012-7-24上午01:45:13
 * @final : King 2020年02月19日下午15:44:00  增加了类型严格约束
 */
final class Convert
{

    /**
     * 将指定的字符串（它将二进制数据编码为 Base64 数字）转换为字符串。
     *
     * @param string $data
     *        指定base64编码的字符串
     * @return string
     */
    public static function base64Encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * 将指定的字符串从Base64解码为 普通字符串
     *
     * @param string $data
     *        指定base64解码的字符串
     * @return string
     */
    public static function base64Decode(string $data): string
    {
        return base64_decode($data);
    }

    /**
     * 将输入的变量转换为bool类型
     *
     * @param $var mixed
     *        输入的变量
     * @return bool
     */
    public static function toBoolean($var): bool
    {
        return (bool)$var;
    }

    /**
     * 将输入的变量转换为int 默认为long int 64位
     *
     * @param $var mixed
     *        输入的变量
     * @return int
     */
    public static function toInt($var): int
    {
        return intval($var);
    }

    /**
     * 将输入的变量转换为浮点数
     *
     * @param $var mixed
     *        输入的变量
     * @return float
     */
    public static function toFloat($var): float
    {
        return floatval($var);
    }
}
?>