<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Gc.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-26上午06:47:05
 * @Description 垃圾回收控制类
 * @Class List
 *        1.GC GC回收控制函数
 * @History <author> <time> <version > <desc>
 *          king 2013-11-26上午06:47:05 1.0 第一次建立该文件
 *          King 2020年02月19日下午15:44:00 stable 1.0.01 审定稳定版本
 */
namespace Tiny\Runtime;

/**
 * 垃圾回收控制类
 *
 * @package Tiny.Runtime
 * @since 2013-11-26上午06:47:31
 * @final 2020年02月19日下午15:44:00 King 审定
 */
final class Gc
{

    /**
     * 强制收集所有现存的垃圾循环周期。
     *
     * @return int
     */
    public static function collect()
    {
        if (gc_enabled())
        {
            return gc_collect_cycles();
        }
    }

    /**
     * 激活循环引用收集器
     *
     * @return void
     */
    public static function enable()
    {
        return gc_enable();
    }

    /**
     * 是否有开启垃圾循环引用收集器
     *
     * @return bool
     */
    public static function isEnable()
    {
        return gc_enabled();
    }

    /**
     * 停用循环引用收集器。
     *
     * @return void
     */
    public static function disable()
    {
        return gc_disable();
    }

    /**
     * 清理zend内存管理的内存管理
     *
     * @return int
     */
    public static function clear()
    {
        return gc_mem_caches();
    }
}
?>