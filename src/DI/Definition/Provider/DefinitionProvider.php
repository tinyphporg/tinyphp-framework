<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name DefinitionProivder.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午2:06:44
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午2:06:44 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\DI\Definition\Provider;

use Tiny\DI\ContainerInterface;
use Tiny\DI\Definition\DefinitionInterface;
use Tiny\DI\Definition\CallableDefinition;
use Tiny\DI\Definition\ObjectDefinition;

/**
 * 定义提供类
 *
 * @package Tiny.DI.Definition
 * @since 2022年1月4日下午4:47:28
 * @final 2022年1月4日下午4:47:28
 */
class DefinitionProvider implements DefinitionProviderInterface
{
    
    /**
     * 所有定义提供者
     *
     * @var array
     */
    protected $definitionProivders = [];
    
    /**
     * 定义文件
     *
     * @var array
     */
    protected $definitionFiles = [];
    
    /**
     * 所有解析的定义
     *
     * @var array
     */
    protected $definitions = [];
    
    /**
     * 构造函数
     *
     * @param array $definitionProivders 预定义的定义提供实例
     */
    public function __construct(array $definitionProivders)
    {
        $this->definitionProivders = $definitionProivders;
    }
    
    /**
     * 增加
     *
     * @param DefinitionProviderInterface $proivder
     */
    public function addDefinitionProivder(DefinitionProviderInterface $proivder)
    {
        $this->definitionProivders[] = $proivder;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinition()
     */
    public function getDefinition(string $name)
    {
        if (key_exists($name, $this->definitions)) {
            return $this->definitions[$name];
        }
        
        foreach ($this->definitionProivders as $proivder) {
            $definition = $proivder->getDefinition($name);
            if ($definition) {
                return $definition;
            }
        }
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinitions()
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }
    
    /**
     * 增加定义类实例
     *
     * @param DefinitionInterface $definition 定义接口
     */
    public function addDefinition(DefinitionInterface $definition): bool
    {
        $name = $definition->getName();
        $this->definitions[$name] = $definition;
        return true;
    }
    
    /**
     * 增加一个定义路径
     *
     * @param mixed $path
     */
    public function addDefinitionFromPath($path)
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->addDefinitionFromPath($p);
            }
            return;
        }
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $this->addDefinitionFromPath($path . '/' . $file);
            }
            $this->definitionFiles[] = $path;
            return;
        }
        
        $this->addDefinitionFromFile($path);
    }
    
    /**
     * 从文件添加定义
     * 
     * @param string $file
     */
    public function addDefinitionFromFile($file)
    {
        if (is_array($file)) {
            foreach ($file as $f) {
                $this->addDefinitionFromFile($f);
            }
            return;
        }
        if ('php' === pathinfo($file, PATHINFO_EXTENSION) && !in_array($file, $this->definitionFiles)) {
            $definitions = include $file;
            if (!is_array($definitions)) {
                return;
            }
            $this->addDefinitionFromArray($definitions);
            $this->definitionFiles[] = $file;
        }
    }
    
    /**
     * 增加一个定义实例集合
     *
     * @param array $sourceDefinitions
     */
    public function addDefinitionFromArray(array $sourceDefinitions)
    {
        if (key_exists('alias', $sourceDefinitions)) {
            $this->addDefinitionAliasFromArray((array)$sourceDefinitions['alias']);
            unset($sourceDefinitions['alias']);
        }
        foreach ($sourceDefinitions as $name => $sourceDefinition) {
           
            if ($this->resolveSourceDefinitionItem($name, $sourceDefinition)) {
                continue;
            }
            $this->resloveSourceDefinition($name, $sourceDefinition);
        }
    }
    
    /**
     * 添加一个别名数组
     * name => ::class
     *
     * @param array $definitionAlias
     */
    public function addDefinitionAliasFromArray(array $definitionAlias)
    {
        foreach ($definitionAlias as $name => $aliasName) {
            $definition = new CallableDefinition($name, function (ContainerInterface $container, string $aliasName) {
                        return $container->get($aliasName);
                }, ['aliasName' => $aliasName]);
            
            $this->addDefinition($definition);
        }
    }
    
    /**
     * 解析定义集合
     * int => ::class
     *
     * @param mixed $sourceDefinition
     * @return bool
     */
    public function resolveSourceDefinitionItem($name, $sourceDefinition): bool
    {
        if (!is_int($name)) {
            return false;
        }
        
        // 0 => definitionInterface
        if ($sourceDefinition instanceof DefinitionInterface) {
            return $this->addDefinition($sourceDefinition);
        }
        
        // 0 => class name
        if (is_string($sourceDefinition) && false !== strpos($sourceDefinition, '\\')) {
            $name = $sourceDefinition;
            $objectDefinition = new ObjectDefinition($name, $sourceDefinition);
            return $this->addDefinition($objectDefinition);
        }
    }
    
    /**
     * 解析源定义
     * name => function(){}
     *
     * @param string $name
     * @param mixed $sourceDefinition
     * @return bool
     */
    public function resloveSourceDefinition($name, $sourceDefinition): bool
    {
        if (!is_string($name)) {
            return false;
        }
        
        // name => DefinitionInterface
        if ($sourceDefinition instanceof DefinitionInterface) {
            $sourceDefinition->setName($name);
            return $this->addDefinition($sourceDefinition);
        }
        
        // name => \Closeure || callable
        if ($sourceDefinition instanceof \Closure || is_callable($sourceDefinition)) {
            
            $definition = new CallableDefinition($name, $sourceDefinition);
            return $this->addDefinition($definition);
        }
        
        return false;
    }
}
?>