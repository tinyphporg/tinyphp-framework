<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Process.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-26上午06:50:38
 * @Description 对当前进程的一些操作和信息描述
 * @Class List
 *        1. Process 进程控制工具类
 * @History <author> <time> <version > <desc>
 *          King 2013-11-26上午06:50:38 1.0 第一次建立该文件
 *          King 2020年02月19日下午15:44:00 stable 1.0.01 审定稳定版本
 */
namespace Tiny\Runtime;

/**
 * 对当前进程的一些操作和信息描述
 *
 * @package Tiny.Runtime
 * @since 2013-11-26上午06:51:37
 * @final King 2020年02月19日下午15:44:00 stable 1.0.01 审定
 */
class Process
{

    /**
     * 返回所有编译并加载模块名的 array
     *
     * @param
     *        void
     * @return array
     */
    public static function getLoadedExtensions(): array
    {
        return get_loaded_extensions();
    }

    /**
     * 判断是否有加载某个扩展
     *
     * @param string $ext
     *        扩展名称
     * @return bool
     */
    public static function extensionLoaded(string $name): bool
    {
        return extension_loaded($name);
    }

    /**
     * 返回某个扩展里的所有函数
     *
     * @param string $ext
     *        扩展名称
     * @return array
     */
    public static function getExtensionFuncs(string $moduleName): array
    {
        return get_extension_funcs($moduleName);
    }

    /**
     * 返回已定义的所有变量数组 包括全局变量和用户自定义变量
     *
     * @return array
     */
    public static function getDefinedVars(): array
    {
        return get_defined_vars();
    }

    /**
     * 获取运行当前进程的用户UID
     *
     * @return int
     */
    public static function uid(): int
    {
        return getmyuid();
    }

    /**
     * 获取执行当前PHP进程用户GroupID
     *
     * @return int
     */
    public static function gid(): int
    {
        return getmygid();
    }

    /**
     * 获取当前进程ID
     *
     * @return int
     */
    public static function pid(): int
    {
        return getmypid();
    }

    /**
     * 获取当前进程的include搜索文件的路径
     *
     * @return string
     */
    public static function getPath(): string
    {
        return get_include_path();
    }

    /**
     * 设置包含路径
     *
     * @param string $path
     *        添加新的路径并返回完整路径
     * @return bool
     */
    public static function setPath($newPath): string
    {
        return set_include_path($newPath);
    }

    /**
     * 获取当前进程已经包含的脚本文件数组
     *
     * @return array
     */
    public static function getIncludeFiles(): array
    {
        return get_included_files();
    }

    /**
     * 获取当前进程使用的峰值内存
     *
     * @return int
     */
    public static function getMemoryPeakUsage(): int
    {
        return memory_get_peak_usage();
    }

    /**
     * 设置进程的生命周期
     *
     * @param int $num
     *        为0 则不限制
     * @return bool
     */
    public static function setTimeLimit($num = 0): bool
    {
        return set_time_limit($num);
    }

    /**
     * 获取当前进程使用的内存
     *
     * @return int
     */
    public static function getMemoryUsage(): int
    {
        return memory_get_usage();
    }

    /**
     * 调用系统数据的信息数组
     *
     * @return array
     */
    public static function getUsage(): array
    {
        return getrusage();
    }

    /**
     * 退出进程
     *
     * @param int $status
     *        退出状态码
     */
    public static function exit(int $status)
    {
        exit($status);
    }
}
?>