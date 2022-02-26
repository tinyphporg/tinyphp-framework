<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月12日下午5:31:11
 * @Class List
 * @Function List
 * @History King 2017年3月12日下午5:31:11 0 第一次建立该文件
 *          King 2017年3月12日下午5:31:11 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Bootstrap;

use Tiny\MVC\Application\ApplicationBase;
use Tiny\DI\ContainerInterface;
use Tiny\MVC\Event\MvcEvent;
use Tiny\MVC\Event\Listener\BootstrapEventListener;

/**
 * 引导基类
 *
 * @package Tiny.Application.Bootstrap
 * @since 2017年3月12日下午5:34:17
 * @final 2017年3月12日下午5:34:17
 */
abstract class Bootstrap implements BootstrapEventListener
{

    /**
     * 当前App运行实例
     *
     * @autowired
     * @var ApplicationBase
     */
    protected ApplicationBase $app;
    
    /**
     * 容器实例
     * @autowired
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * 执行初始化数组
     *
     * @return array
     */
    final public function onBootstrap(MvcEvent $event, array $params)
    {
        $reflectionClass = new \ReflectionClass($this);
        $reflectionMethods = $reflectionClass->getMethods();
        $proivderParameters = [
            MvcEvent::class => $event,
            'params' => $params,
            get_class($this) => $this,
            self::class => $this,
        ];
        
        foreach($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod instanceof \ReflectionMethod) {
                if ($reflectionMethod->isStatic()) {
                    continue;
                }
                
                $methodName = $reflectionMethod->getName();
                if (strlen($methodName) < 5 || strpos($methodName, 'init') !== 0)
                {
                    continue;
                }
                $this->container->call([$this, $methodName], $proivderParameters);
                
            }
        }
    }
}
?>