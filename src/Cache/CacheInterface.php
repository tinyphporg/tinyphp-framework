<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name CacheInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午1:33:17
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午1:33:17 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Cache;

/**
 * 缓存接口
 * 遵循psr/simple-cache规范，并在此基础上，增加严格的类型约束
 *
 * @package Tiny.Cache
 * @since Fri Dec 16 22 29 08 CST 2011
 * @final Fri Dec 16 22 29 08 CST 2011
 *        King 2020年02月24日上午12:06:00 stable 1.0 审定稳定版本
 *        King 2021年11月26日下午2:55:38 更新为遵守psr-6的simple-cache规范
 */
interface CacheInterface
{
    
    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     *       
     * @return mixed key存在时返回缓存值，不存在时返回$default
     */
    public function get(string $key, $default = null);
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     *       
     * @return bool
     */
    public function set(string $key, $value, int $ttl = 0);
    
    /**
     * 移除缓存
     *
     * @param string $key 删除key对应的缓存值
     *       
     * @return bool 删除成功返回true, 失败则为false
     */
    public function delete(string $key);
    
    /**
     * 清空所有的缓存字典
     *
     * @return bool 成功为true否则为false
     */
    public function clear();
    
    /**
     * 获取缓存
     *
     * @param string $key 缓存中不重复的键名
     * @param mixed $default 缓存没有命中时返回默认值
     *       
     * @return mixed key存在时返回缓存值，不存在时返回$default
     */
    public function getMultiple(array $keys, $default = null);
    
    /**
     * 设置缓存
     *
     * @param string $key 缓存的键 $key为array时 可以批量设置缓存
     * @param mixed $value 经过serialize的缓存值
     * @param int $ttl 缓存过期时间
     *       
     * @return bool
     */
    public function setMultiple(array $values, int $ttl = 0);
    
    /**
     * 删除缓存
     *
     * @param array $key 删除key对应的缓存值
     *       
     * @return bool 键数组成功删除则返回true，否则为false
     */
    public function deleteMultiple(array $keys);
    
    /**
     * 缓存是否存在
     *
     * @param string $key 缓存键
     *       
     * @return bool 存在返回true 否则为false
     */
    public function has(string $key);
}
?>