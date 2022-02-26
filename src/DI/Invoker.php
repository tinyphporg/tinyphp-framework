<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name Invoker.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:54:55
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:54:55 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI;

use Tiny\DI\Injection\Injection;
use Tiny\DI\Injection\InjectionInterface;
use Tiny\DI\Definition\NotFoundClassException;

/**
 * 派发器
 *
 * @package Tiny.DI
 * @since 2022年1月4日下午7:47:04
 * @final 2022年1月4日下午7:47:04
 */
class Invoker
{
    
    /**
     * 容器实例
     *
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * 注入器实例
     *
     * @var Injection
     */
    private $injection;
    
    /**
     * 构造函数
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, InjectionInterface $injection)
    {
        $this->container = $container;
        $this->injection = $injection ?: new Injection($container);
    }
    
    /**
     * 调用回调函数
     *
     * @param callable $callable
     * @param array $parameters
     * @return mixed
     */
    public function call($callable, array $parameters = [])
    {
        $callable = $this->resolveCallable($callable);
        $callableReflection = $this->createCallableReflection($callable);
        if ($callableReflection instanceof \ReflectionMethod && !$callableReflection->isPublic())
        {
            $callableReflection->setAccessible(true);
        }
        $args = $this->injection->getParameters($callableReflection, $parameters, []);
        return call_user_func_array($callable, $args);
    }
    
    /**
     * 创建回调实例的反射实例
     *
     * @param callable $callable
     * @throws NotCallableException
     * @return \ReflectionFunctionAbstract
     */
    public function createCallableReflection($callable): \ReflectionFunctionAbstract
    {
        
        // Closure
        if ($callable instanceof \Closure) {
            return new \ReflectionFunction($callable);
        }
        
        // Array callable
        if (is_array($callable)) {
            [
            $class,
            $method
            ] = $callable;
            
            if (!method_exists($class, $method)) {
                throw NotCallableException::fromInvalidCallable($callable);
            }
            
            return new \ReflectionMethod($class, $method);
        }
        
        // Callable object (i.e. implementing __invoke())
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            return new \ReflectionMethod($callable, '__invoke');
        }
        
        // Standard function
        if (is_string($callable) && function_exists($callable)) {
            return new \ReflectionFunction($callable);
        }
        
        throw new NotCallableException(
            sprintf('%s is not a callable', is_string($callable) ? $callable : 'Instance of ' . get_class($callable)));
    }
    
    /**
     * 解析回调函数
     *
     * @param callable $callable
     * @throws NotCallableException
     * @return \Closure|mixed
     */
    protected function resolveCallable($callable)
    {
        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable, 2);
        }
        
        $callable = $this->resolveFromContainer($callable);
        
        if (!is_callable($callable)) {
            throw new NotCallableException(
                sprintf('%s is not a callable',
                    is_object($callable) ? 'Instance of ' . get_class($callable) : var_export($callable, true)));
        }
        return $callable;
    }
    
    /**
     * 从容器实例中解析参数
     *
     * @param callable $callable
     * @throws NotFoundClassException
     * @throws NotCallableException
     * @return \Closure|mixed|\Closure|mixed
     */
    private function resolveFromContainer($callable)
    {
        // Shortcut for a very common use case
        if ($callable instanceof \Closure) {
            return $callable;
        }
        
        // If it's already a callable there is nothing to do
        if (is_callable($callable)) {
            // TODO with PHP 8 that should not be necessary to check this anymore
            if (!$this->isStaticCallToNonStaticMethod($callable)) {
                return $callable;
            }
        }
        
        // The callable is a container entry name
        if (is_string($callable)) {
            try {
                return $this->container->get($callable);
            } catch (NotFoundClassException $e) {
                throw $e;
            }
        }
        
        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_array($callable) && is_string($callable[0])) {
            try {
                // Replace the container entry name by the actual object
                $callable[0] = $this->container->get($callable[0]);
                return $callable;
            } catch (NotFoundClassException $e) {
                if ($this->container->has($callable[0])) {
                    throw $e;
                }
                throw new NotCallableException(
                    sprintf('Cannot call %s() on %s because it is not a class nor a valid container entry', $callable[1],
                        $callable[0]));
            }
        }
        
        // Unrecognized stuff, we let it fail later
        return $callable;
    }
    
    /**
     * 是否为静态回调
     *
     * @param callable $callable
     * @return bool
     */
    private function isStaticCallToNonStaticMethod($callable): bool
    {
        if (is_array($callable) && is_string($callable[0])) {
            [
            $class,
            $method
            ] = $callable;
            
            if (!method_exists($class, $method)) {
                return false;
            }
            
            $reflection = new \ReflectionMethod($class, $method);
            
            return !$reflection->isStatic();
        }
        
        return false;
    }
}
?>