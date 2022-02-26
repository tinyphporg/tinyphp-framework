<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ContainerInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:49:44
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:49:44 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI;

/**
 * 容器接口
 *
 * @package Tiny.DI
 * @since 2021年11月26日上午11:32:43
 * @final 2021年11月26日上午11:32:43
 *
 */
interface ContainerInterface
{
    
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $name Identifier of the entry to look for.
     *
     * @throws NotFoundException No entry was found for **this** identifier.
     * @throws ContainerException while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $name);
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($name)` returning true does not mean that `get($name)` will not throw an exception.
     * It does however mean that `get($name)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $name Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $name): bool;
}
?>