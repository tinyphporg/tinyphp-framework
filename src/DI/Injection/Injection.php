<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Autowiring.php
 * @author King
 * @version stable 1.0
 * @Date 2022年1月1日下午6:23:09
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2022年1月1日下午6:23:09 第一次建立该文件
 *          King 2022年1月1日下午6:23:09 修改
 *         
 */
namespace Tiny\DI\Injection;

use Tiny\DI\ContainerInterface;


/**
* 注入器
* 
* @package Tiny.DI.Injection
* @since 2022年1月4日下午4:29:49
* @final 2022年1月4日下午4:29:49
*/
class Injection implements InjectionInterface
{

    /**
     * 注入的容器
     * 
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 参数注入器
     * 
     * @var ParameterInjection
     */
    protected $parameterInjection;

    /**
     *  实例注入器
     *  
     * @var ObjectInjection
     */
    protected $objectInjection;

    /**
     *  构造函数
     *  
     * @param ContainerInterface $container 注入取值的容器
     * @param bool $isAnnotationAutowiring 是否通过注解注入函数和构造函数
     */
    public function __construct(ContainerInterface $container, bool $isAnnotationAutowiring = true)
    {
        $this->container = $container;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Injection\InjectionInterface::getParameters()
     */
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $proivderParameters = []): array
    {
        return $this->getParameterInjection()->getParameters($reflection, $proivderParameters);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\DI\Injection\InjectionInterface::injectProperties()
     */
    public function injectObject(\ReflectionClass $classReflection, $object, array $resolvedParameters = [])
    {
        return $this->getObjectInjection()->injectObject($classReflection, $object, $resolvedParameters);
    }
    
    /**
     * 是否自动注解并加载类
     * 
     * @param string $className
     * @return bool
     */
    public function isAutowiredClass(string $className)
    {
        return $this->getObjectInjection()->isAutowiredClass($className);
    }
    
    /**
     * 获取参数注入器
     * 
     * @return \Tiny\DI\Injection\ParameterInjection
     */
    protected function getParameterInjection()
    {
        if (!$this->parameterInjection)
        {
            $this->parameterInjection = new ParameterInjection($this->container, $this);
        }
        return $this->parameterInjection;
    }
    
    /**
     * 获取属性注入器
     * 
     * @return \Tiny\DI\Injection\ObjectInjection
     */
    protected function getObjectInjection()
    {
        if (!$this->objectInjection)
        {
            $this->objectInjection = new ObjectInjection($this->container, $this);
        }
        return $this->objectInjection;
    }
}
?>