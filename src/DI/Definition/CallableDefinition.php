<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name CallableDefinition.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午2:11:34
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午2:11:34 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition;

/**
 * 回调定义类
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午5:08:15
 * @final 2022年1月4日下午5:08:15
 */
class CallableDefinition implements DefinitionInterface
{
    
    /**
     * 回调函数实例
     *
     * @var callable
     */
    protected $callable;
    
    /**
     * 定义名
     *
     * @var string
     */
    protected $name;
    
    /**
     * 附加参数
     * 
     * @var array
     */
    protected $parameters = [];
    
    /**
     *
     * @param string $name
     * @param callable $value
     */
    public function __construct(string $name, callable $callable, array $parameters = [])
    {
        $this->name = (string)$name;
        $this->callable = $callable;
        $this->parameters = $parameters;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Tiny\DI\Definition\DefinitionInterface::setName()
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * 获取回调函数的参数
     * 
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
    
    /**
     * 设置回调函数的参数
     * 
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;      
    }
    
    /**
     * 设置实例的回调函数
     * 
     * @param callable $callable
     */
    public function setCallable(callable $callable)
    {
        $this->callable = $callable;
    }
    
    /**
     * 获取回调实例
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }
}
?>