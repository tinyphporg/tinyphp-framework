<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name CallableResolver.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:08:38
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:08:38 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition\Resolver;

use Tiny\DI\Definition\Resolver\DefinitionResolverInterface;
use Tiny\DI\ContainerInterface;
use Tiny\DI\Definition\DefinitionInterface;

/**
 * 回调解析类
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午5:47:58
 * @final 2022年1月4日下午5:47:58
 */
class CallableResolver implements DefinitionResolverInterface
{
    
    /**
     * 容器实例
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 构造函数
     *
     * @param ContainerInterface $container 容器实例
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\Resolver\DefinitionResolverInterface::resolve()
     */
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        return $this->container->call($definition->getCallable());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\Resolver\DefinitionResolverInterface::isResolvable()
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool
    {
        return is_callable($definition->getCallable());
    }
}
?>