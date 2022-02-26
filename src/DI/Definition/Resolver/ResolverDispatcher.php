<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ResolverDispatcher.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:06:49
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:06:49 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition\Resolver;

use Tiny\DI\ContainerInterface;
use Tiny\DI\Injection\InjectionInterface;
use Tiny\DI\Definition\DefinitionInterface;
use Tiny\DI\Definition\SelfResolvingDefinition;
use Tiny\DI\Definition\CallableDefinition;
use Tiny\DI\Definition\InstanceDefinition;
use Tiny\DI\Definition\ObjectDefinition;

/**
 * 定义解析派发器
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午5:21:46
 * @final 2022年1月4日下午5:21:46
 */
class ResolverDispatcher implements DefinitionResolverInterface
{
    
    /**
     * 容器实例
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 回调类解析器
     *
     * @var CallableResolver
     */
    protected $callableResolver;
    
    /**
     * 对象解析器
     *
     * @var ObjectResolver
     */
    protected $objectResolver;
    
    /**
     * 实例解析器
     *
     * @var InstanceResolver
     */
    protected $instanceResolver;
    
    /**
     * 注入器实例
     *
     * @var InjectionInterface
     */
    protected $injection;
    
    /**
     * 构造函数
     *
     * @param ContainerInterface $container
     * @param InjectionInterface $injection
     */
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        $this->injection = $injection;
    }
    
    /**
     * 解析并返回解析后的值
     *
     * @param DefinitionInterface $definition
     * @param array $parameters
     * @return mixed
     */
    public function resolve(DefinitionInterface $definition, array $parameters = [])
    {
        if ($definition instanceof SelfResolvingDefinition) {
            return $definition->resolve($this->container);
        }
        return $this->getDefinitionResolver($definition)->resolve($definition, $parameters);
    }
    
    /**
     * 是否可解析
     *
     * @param DefinitionInterface $definition
     * @param array $parameters
     * @return bool
     */
    public function isResolvable(DefinitionInterface $definition, array $parameters = []): bool
    {
        if ($definition instanceof SelfResolvingDefinition) {
            return $definition->isResolvable($this->container);
        }
        
        return $this->getDefinitionResolver($definition)->isResolvable($definition, $parameters);
    }
    
    /**
     * 根据定义类型获取一个默认的解析器
     *
     * @param DefinitionInterface $definition 定义实例
     * @throws \RuntimeException 找不到合适的解析器时抛出
     *        
     * @return DefinitionResolverInterface 返回的解析类实例
     */
    private function getDefinitionResolver(DefinitionInterface $definition): DefinitionResolverInterface
    {
        switch (true) {
            case $definition instanceof CallableDefinition:
                if (!$this->callableResolver) {
                    $this->callableResolver = new CallableResolver($this->container, $this);
                }
                return $this->callableResolver;
            case $definition instanceof InstanceDefinition:
                if (!$this->instanceResolver) {
                    $this->instanceResolver = new InstanceResolver($this->container, $this->injection);
                }
                return $this->instanceResolver;
            case $definition instanceof ObjectDefinition:
                if (!$this->objectResolver) {
                    $this->objectResolver = new ObjectResolver($this->container, $this->injection);
                }
                return $this->objectResolver;
            
            default:
                throw new \RuntimeException('No definition resolver was configured for definition of type ' . get_class($definition));
        }
    }
}
?>