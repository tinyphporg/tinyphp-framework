<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ObjectInjection.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:46:51
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:46:51 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Injection;

use Tiny\DI\ContainerInterface;

/**
 * 属性注入器
 *
 * @package namespace
 * @since 2022年1月4日下午3:12:44
 * @final 2022年1月4日下午3:12:44
 */
class ObjectInjection
{
    /**
     * 提供取值的容器实例
     *
     * @var ContainerInterface
     */
    private $container;
    
    /**
     *
     * @var InjectionInterface
     */
    private $injection;
    
    /**
     * 构造函数
     *
     * @param ContainerInterface $container 提供取值的容器实例
     */
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        $this->injection = $injection;
    }
    
    /**
     *  注入对象
     *  
     * @param \ReflectionClass $classReflection
     * @param object $object
     */
    public function injectObject(\ReflectionClass $classReflection, object $object)
    {
        $this->injectProperties($classReflection, $object);
        $this->injectMethods($classReflection, $object);
    }
    
    /**
     * 注入属性成员
     *
     * @param \ReflectionClass $reflectionClassInstance
     * @param object $classInstance
     */
    public function injectProperties(\ReflectionClass $classReflection, object $object)
    {
        $properties = $classReflection->getProperties();
        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $this->readProperty($property, $object);
        }
        
        $class = $classReflection;
        while ($class = $class->getParentClass()) {
            
            foreach ($class->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
                if ($property->isStatic()) {
                    continue;
                }
                $this->readProperty($property, $object);
            }
        }
    }
    
    /**
     * 注入所有的成员函数
     * 
     * @param \ReflectionClass $classReflection
     * @param object $object
     */
    public function injectMethods(\ReflectionClass $classReflection, object $object)
    {
        $methods = $classReflection->getMethods();
        foreach($methods as $method)
        {
            $this->injectMethod($method, $object);
        }
    }
    
    /**
     * 注入成员函数
     * 
     * @param \ReflectionMethod $method
     * @param object $object
     */
    protected function injectMethod(\ReflectionMethod $method, object $object)
    {
        if($method->isConstructor() || $method->isDestructor() || $method->isAbstract())
        {
            return;
        }
        $methodComment = $method->getDocComment();
        if (!$this->isAutowired($methodComment))
        {
            return;
        }
        $args = $this->injection->getParameters($method);
        if ($method->isPrivate() || $method->isProtected())
        {
            $method->setAccessible(true);
        }
        $method->invokeArgs($object, $args);
    }
    
    /**
     * 读取成员属性的值
     *
     * @param \ReflectionProperty $property
     * @param object $object
     */
    protected function readProperty(\ReflectionProperty $property, object $object)
    {
        $propertyComment = $property->getDocComment();
        if (! $propertyComment || ! $this->isAutowired($propertyComment)) {
            return;
        }
        $propertyValue = $this->readPropertyFromPHPType($property);
        if (! $propertyValue) {
            $propertyValue = $this->readPropertyFromAnnotation($property, $propertyComment);
        }
        if (! $propertyValue) {
            return;
        }
        if (! $property->isPublic()) {
            $property->setAccessible(true);
        }
        $property->setValue($object, $propertyValue);
    }
    
    /**
     * 通过注解读取成员属性的值
     *
     * @param \ReflectionProperty $property
     * @param string $propertyComment
     * @return void|\Tiny\DI\ContainerInterface|mixed
     */
    protected function readPropertyFromAnnotation(\ReflectionProperty $property, string $propertyComment)
    {
        $namespace = $property->getDeclaringClass()->getNamespaceName();
        if (! preg_match('/\s*\*\s+@var\s+([\\\\\w]+)/i', $propertyComment, $out)) {
            return;
        }
        $propertyClass = $out[1];
        if (strpos($propertyClass, "\\") === false) {
            $propertyClass = $namespace . '\\' . $propertyClass;
        } elseif ($propertyClass[0] == '\\') {
            
            $propertyClass = substr($propertyClass, 1);
        }
        
        return $this->readPropertyFromContainer($propertyClass);
    }
    
    /**
     * 从PHP类型读取
     * @param \ReflectionProperty $property
     * @return boolean|\Tiny\DI\ContainerInterface|mixed
     */
    protected function readPropertyFromPHPType(\ReflectionProperty $property)
    {
        $propertyType = $property->getType();
        if (! $propertyType) {
            return false;
        }
        if (! $propertyType instanceof \ReflectionNamedType) {
            // Union types are not supported
            return false;
        }
        if ($propertyType->isBuiltin()) {
            // Primitive types are not supported
            return false;
        }
        
        $propertyClass = $propertyType->getName();
        return $this->readPropertyFromContainer($propertyClass);
    }
    
    /**
     * 从容器中读取值
     *
     * @param string $name 类名
     *
     * @return \Tiny\DI\ContainerInterface|mixed
     */
    protected function readPropertyFromContainer($name)
    {
        if ($name === ContainerInterface::class) {
            return $this->container;
        } elseif ($this->container->has($name)) {
            return $this->container->get($name);
        }
    }
    
    /**
     * 是否自动注解
     *
     * @param string $comment
     * @return boolean
     */
    protected function isAutowired(string $comment)
    {
        return stripos($comment, '@autowired') !== false;
    }
}
?>