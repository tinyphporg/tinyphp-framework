<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ObjectResolver.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:10:18
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:10:18 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition\Resolver;

use Tiny\DI\Injection\Injection;
use Tiny\DI\ContainerInterface;
use Tiny\DI\Injection\InjectionInterface;
use Tiny\DI\Definition\DefinitionInterface;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\Definition\InvalidDefinitionException;

/**
 * 实例定义解析器
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午5:55:16
 * @final 2022年1月4日下午5:55:16
 */
class ObjectResolver implements DefinitionResolverInterface
{
    /**
     * 容器实例
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 注入器实例
     *
     * @var Injection
     */
    protected $injection;
    
    /**
     * 类的反射器实例
     *
     * @var \ReflectionClass
     */
    protected $reflectionClass;
    
    
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        $this->injection = $injection;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\Resolver\DefinitionResolverInterface::resolve()
     */
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        return $this->createInstance($definition, $parameters);
    }
    
    /**
     * 根据定义创建实例 并自动注入
     *
     * @param ObjectDefinition $definition
     * @param array $parameters
     * @throws InvalidDefinitionException
     * @return mixed
     */
    protected function createInstance(ObjectDefinition $definition, array $parameters = [])
    {
        $className = $definition->getClassName();
        
        if (!class_exists($className)) {
            throw new InvalidDefinitionException(sprintf('Entry "%s" cannot be resolved: the class doesn\'t exist', $definition->getName()));
        }
        $classReflection = new \ReflectionClass($className);
        if (!$classReflection->isInstantiable())
        {
            throw new InvalidDefinitionException(sprintf('Entry "%s" cannot be resolved: the class is not instantiable', $definition->getName()));
        }
        
        $args = [];
        $constructor = $classReflection->getConstructor();
        if ($constructor)
        {
            $args = $this->injection->getParameters($constructor,$parameters);
        }
        $object = new $className(...$args);
        
        // inject
        $this->injection->injectObject($classReflection, $object);
        return $object;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\Resolver\DefinitionResolverInterface::isResolvable()
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool
    {
        $className = $definition->getClassName();
        if (!class_exists($className)) {
            return false;
        }
        
        $classReflection = new \ReflectionClass($className);
        if (!$classReflection->isInstantiable())
        {
            return false;
        }
        return true;
    }
}
?>