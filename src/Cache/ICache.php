<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ICache.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月8日下午11:58:11
 * @Class List
 * @Function List
 * @History King 2017年4月8日下午11:58:11 0 第一次建立该文件
 *          King 2017年4月8日下午11:58:11 1 上午修改
 *          King 2020年02月24日15:39:00 stable 1.0.01 审定稳定版本
 */
namespace Tiny\Cache;

/**
 * 缓存适配器的统一接口
 *
 * @package Cache
 * @since Fri Dec 16 22 29 08 CST 2011
 * @final Fri Dec 16 22 29 08 CST 2011
 *        King 2020年02月24日上午12:06:00 stable 1.0.01 审定稳定版本
 */
interface ICache
{

    /**
     * 获取缓存
     *
     * @param string $key
     *        获取缓存的键名 如果$key为数组 则可以批量获取缓存
     * @return mixed
     */
    public function get($key);

    /**
     * 设置缓存
     *
     * @param string $key
     *        缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value
     *        缓存的值 $key为array时 为设置生命周期的值
     * @param int $life
     *        缓存的生命周期
     * @return bool
     */
    public function set($key, $value = NULL, $life = NULL);

    /**
     * 移除缓存
     *
     * @param string $key
     *        缓存的键 $key为array时 可以批量删除
     * @return bool
     */
    public function remove($key);

    /**
     * 缓存是否存在
     *
     * @param string $key
     * @return bool
     */
    public function exists($key);

    /**
     * 清空所有缓存
     *
     * @return bool
     */
    public function clean();
}
?>