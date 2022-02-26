<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Event.php
 * @author King
 * @version stable 2.0
 * @Date 2022年1月10日下午7:05:52
 * @Class List class
 * @Function List function_container
 * @History King 2022年1月10日下午7:05:52 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Event;

/**
 * 事件
 *
 * @package Tiny.Event
 * @since 2022年1月11日下午11:32:55
 * @final 2022年1月11日下午11:32:55
 */
class Event implements EventInterface
{
    
    /**
     * 错误事件
     *
     * @var
     */
    const EVENT_ONEXCEPTION = ExceptionEventListener::class;
    
    /**
     * 允许触发的事件集合
     *
     * @var array
     */
    protected $triggeredEvents;
    
    /**
     * 事件名
     *
     * @var string
     */
    protected $name;
    
    /**
     * event params
     *
     * @var array
     */
    protected $params = [];
    
    /**
     *
     * @var bool 继续或者停止事件冒泡
     */
    protected $stopPropagation = false;
    
    /**
     * 构造函数
     *
     * @param string $name
     * @param array $params
     * @throws \Exception
     */
    public function __construct($name, array $params = [])
    {
        $this->setName($name);
        $this->params = $params;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Event\EventInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Event\EventInterface::setName()
     */
    public function setName(string $name)
    {
        if (!$this->isTriggered($name)) {
            throw new EventException('This event handler %s is not allowed to be triggered', $name);
        }
        $this->name = $name;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Event\EventInterface::getParams()
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Event\EventInterface::setParams()
     */
    public function setParams(array $params)
    {
        $this->params += $params;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Event\EventInterface::stopPropagation()
     */
    public function stopPropagation($flag = true)
    {
        $this->stopPropagation = (bool)$flag;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\Event\EventInterface::propagationIsStopped()
     */
    public function propagationIsStopped()
    {
        return $this->stopPropagation;
    }
    
    /**
     * 是否允许的EVENT
     *
     * @param string $name
     * @return boolean
     */
    protected function isTriggered($name)
    {
        // return true;
        if (!is_array($this->triggeredEvents)) {
            $reflectionClass = new \ReflectionClass($this);
            $this->triggeredEvents = $reflectionClass->getConstants();
            while ($reflectionClass = $reflectionClass->getParentClass()) {
                $this->triggeredEvents = array_merge($reflectionClass->getConstants(), $this->triggeredEvents);
            }
        }
        return in_array($name, $this->triggeredEvents);
    }
}

?>