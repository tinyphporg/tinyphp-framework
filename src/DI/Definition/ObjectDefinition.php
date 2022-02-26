<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ObjectDefinition.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午3:01:42
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午3:01:42 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition;

/**
 * 对象定义
 * 
 * @package namespace
 * @since 2022年1月4日下午5:15:36
 * @final 2022年1月4日下午5:15:36
 */
class ObjectDefinition implements DefinitionInterface
{
    /**
     * 定义名
     *
     * @var string
     */
    protected $name;
    
    /**
     * 类名
     * @var string
     */
    protected $className;
    
    public function __construct($name, $className)
    {
        $this->name = $name;
        $this->className = $className;
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
     * 获取类名
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
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
}
?>