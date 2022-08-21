<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name EventManager.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午4:02:58
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午4:02:58 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Event;

use Tiny\DI\ContainerInterface;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\MVC\Event\MvcEvent;

/**
 * 事件管理器
 *
 * @package Tiny.Event
 * @since 2022年1月11日下午9:49:55
 * @final 2022年1月11日下午9:49:55
 */
class EventManager
{
    
    /**
     * 当前容器实例
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * 事件监听者的集合
     *
     * @var array
     */
    protected $eventListeners = [];
    
    /**
     * 事件句柄所属事件函数的集合
     *
     * @var array
     */
    protected $eventHandlerMethods = [];
    
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
     * 添加事件监听者
     *
     * @param string $listenerclass the classname of EventListenerInterface
     *        EventListenerInterface $listenerclass 监听接口实例
     * @param int $priority 优先级
     */
    public function addEventListener($eventListener, int $priority = 0)
    {
        if (is_array($eventListener)) {
            foreach ($eventListener as $elistener) {
                $this->addEventListener($elistener, $priority);
            }
            return true;
        }
        
        if ($eventListener instanceof EventListenerInterface) {
            return $this->addToListeners(get_class($eventListener), $eventListener, $priority);
        }
        
        if (!is_string($eventListener)) {
            throw new EventException(sprintf('Illegal event handle:%s', gettype($eventListener)));
        }
        
        if (key_exists($eventListener, $this->eventListeners)) {
            return false;
        }
        
        return $this->addToListeners($eventListener, null, $priority);
    }
    
    /**
     * 添加到事件监听器列表里
     *
     * @param string $className
     * @param EventListenerInterface $instance
     * @param number $priority
     */
    protected function addToListeners($className, $instance = null, int $priority = 0)
    {
        $this->eventListeners[] = [
            'class' => $className,
            'instance' => $instance,
            'priority' => $priority
        ];
        return true;
    }
    
    /**
     *
     * @param string $listenerClass 监听者类名
     *       
     * @return EventListenerInterface
     */
    protected function factory(string $listenerClass)
    {
        if (!$this->container->has($listenerClass)) {
            $this->container->set($listenerClass, new ObjectDefinition($listenerClass, $listenerClass));
        }
        $eventListener = $this->container->get($listenerClass);
        if (!$eventListener instanceof EventListenerInterface) {
            throw new EventException(sprintf("EventLister %s must be instance of %s", $listenerClass, EventListenerInterface::class));
        }
        return $eventListener;
    }
    
    /**
     * 获取事件句柄函数的事件函数
     *
     * @param string $handlerName
     * @throws EventException
     * @return array|null[]
     */
    protected function getEventHandlerMethods(string $handlerName)
    {
        if (key_exists($handlerName, $this->eventHandlerMethods)) {
            return $this->eventHandlerMethods[$handlerName];
        }
        
        if (!(class_exists($handlerName) || interface_exists($handlerName))) {
            throw new EventException(sprintf('EventHandler %s is not exists!', $handlerName));
        }
        
        // 获取句柄的执行函数
        $methods = [];
        $handlerReflection = new \ReflectionClass($handlerName);
        $reflectionMethods = $handlerReflection->getMethods();
        if (!$reflectionMethods) {
            return $methods;
        }
        
        //
        foreach ($reflectionMethods as $reflectionMethod) {
            if ($reflectionMethod->isStatic()) {
                continue;
            }
            $methods[] = $reflectionMethod->getName();
        }
        
        $this->eventHandlerMethods[$handlerName] = $methods;
        return $methods;
    }
    
    /**
     * 触发事件
     *
     * @param EventInterface $event 事件实例
     */
    public function triggerEvent(EventInterface $event)
    {
        // method
        $eventName = $event->getName();
        list($handlerName, $eventMethod) = explode('.', $eventName);
        $methods = $this->getEventHandlerMethods($handlerName);
        if ($eventMethod && !in_array($eventMethod, $methods)) {
            throw new EventException('EventHandler:%s have not method %s', $handlerName, $eventMethod);
        }
        if ($eventMethod) {
            $methods = [
                $eventMethod
            ];
        }
        
        // privoder paramtter
        $params = $event->getParams();
        $eparams = $params + [
            EventInterface::class => $event,
            Event::class => $event,
            get_class($event) => $event,
        ];
        
        $eparams['params'] = $params;
        
        // foreach
        array_multisort(array_column($this->eventListeners, 'priority'), $this->eventListeners, SORT_ASC, SORT_NUMERIC);
        foreach ($this->eventListeners as &$listener) {
            $listenerInstance = $listener['instance'];
            if (!$listenerInstance) {
                $listenerInstance = $this->factory($listener['class']);
                $listener['instance'] = $listenerInstance;
            }
            
            if (!$listenerInstance instanceof $handlerName) {
                continue;
            }
            foreach ($methods as $methodName) {
                $this->container->call([
                    $listenerInstance,
                    $methodName
                ], $eparams);
                if ($event->propagationIsStopped()) {
                    return;
                }
            }
        }
    }
}
?>