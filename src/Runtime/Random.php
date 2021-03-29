<?php
/**
 *
 * @copyright (C), 2011-, King
 * @name Random.php
 * @author King
 * @version Beta 1.0
 * @Date 2013-4-1下午02:57:02
 * @Description 随机类
 * @Class List
 *        1. Random 随机生成工具类
 * @History <author> <time> <version > <desc>
 *          King 2013-4-1下午02:57:02 Beta 1.0 第一次建立该文件
 *          King 2020年02月19日下午15:44:00 stable 1.0.01 审定稳定版本
 *
 */
namespace Tiny\Runtime;

/**
 * 产生随机数字或者随机字符串
 *
 * @package Tiny.Runtime
 * @since 2012-7-24上午02:00:49
 * @final 2020年02月19日下午15:44:00 添加返回类型强约束
 */
class Random
{

    /**
     * 产生一个随机数
     *
     * @param int $min
     *        最小值 只有一个参数时，为最大随机值
     * @param int $max
     *        最大值 默认为0
     * @return int
     */
    public static function rand($min, $max = 0): int
    {
        return ($max > 0 ? mt_rand($min, $max) : mt_rand(0, $min));
    }

    /**
     * 生成一个0-1之间的随机数
     *
     * @return float
     */
    public static function sample(): float
    {
        return floatval(mt_rand(0, 10000000000) / 10000000000);
    }

    /**
     * 产生指定长度和类型的随机字符串
     *
     * @param int $length
     *        字符串长度
     * @param int $type
     *        default 0
     *        0 包含字符串和数字
     *        1 只包含数字
     *        2 只包含字母
     *        3 包含小写字母
     *        4 只包含大写字母
     * @return string
     */
    public static function randStr($length = 4, $type = 0): string
    {
        $strs = [
            0 => 'ABCDEFGHIJKLMNOPQRSTVWUXYZabcdefghijklmnopqrstvwuxyz0123456789',
            1 => '0123456789',
            2 => 'ABCDEFGHIJKLMNOPQRSTVWUXYZabcdefghijklmnopqrstvwuxyz',
            3 => 'abcdefghijklmnopqrstvwuxyz',
            4 => 'ABCDEFGHIJKLMNOPQRSTVWUXYZ'
        ];

        $ret = '';
        $str = (isset($strs[$type])) ? $strs[$type] : $strs[0];
        $strlen = strlen($str);
        for ($i = 0; $i < $length; $i++)
        {
            $ret .= $str[mt_rand(0, $strlen)];
        }
        return $ret;
    }

    /**
     * 产生唯一性的UUID
     *
     * @param string $prefix
     *        前缀
     * @return string
     */
    public static function uuid($prefix = ''): string
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4) . '-';
        $uuid .= substr($chars, 20, 12);
        return $prefix . $uuid;
    }
}
?>