<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name Container.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月26日上午11:32:43 0 第一次建立该文件
 *          King 2021年11月26日上午11:32:43 1 修改
 *          King 2021年11月26日上午11:32:43 stable 1.0.01 审定
 */
namespace Tiny\Container;

use Tiny\Config\Configuration;

/**
 * 容器接口
 * 
 * @package Tiny.Container
 * @since 2021年11月26日上午11:32:43
 * @final 2021年11月26日上午11:32:43
 *
 */
interface ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for **this** identifier.
     * @throws ContainerException while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $id);
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool;
}



/**
 * 
 * 容器
 * 
 * @package Tiny.Container
 * @since 2021年11月26日上午11:32:43
 * @final 2021年11月26日上午11:32:43
 *
 */
class Container implements ContainerInterface
{
    protected $config;
    
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for **this** identifier.
     * @throws ContainerException while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get(string $id)
    {
        
    }
    
    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has(string $id): bool{}
}
?>