<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ModeleProvider.php
 * @author King
 * @version stable 2.0
 * @Date 2022年8月16日下午2:29:13
 * @Class List class
 * @Function List function_container
 * @History King 2022年8月16日下午2:29:13 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Module;

use Tiny\DI\Definition\Provider\DefinitionProviderInterface;
use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\ContainerInterface;
use Tiny\DI\Definition\Provider\DefinitionProvider;
use Tiny\MVC\Module\Source\ModuleSource;
use Tiny\MVC\Module\Parser\ModuleParser;
use Tiny\MVC\Module\Util\StaticCopyer;

/**
 * 模块容器定义类
 *
 * @package Tiny.MVC.Module
 * @since 2022年8月16日下午2:30:23
 * @final 2022年8月16日下午2:30:23
 */
class ModuleProvider implements DefinitionProviderInterface
{
    
    /**
     * 模块实例在容器内的存储前缀
     *
     * @var string
     */
    const MODULE_CONTAINER_PREFIX = 'app.module.';
    
    /**
     * 当前模块管理器实例
     *
     * @var ModuleManager
     */
    protected $moduleManager;
    
    /**
     * 已启用的模块命名空间数组
     *
     * @var array
     */
    protected $activateNamespaces = [];
    
    /**
     * 初始化
     *
     * @param ModuleManager $moduleManager
     */
    public function __construct(ModuleManager $moduleManager, ContainerInterface $container)
    {
        $provider = $container->get(DefinitionProvider::class);
        $provider->addDefinitionProivder($this);
        $this->moduleManager = $moduleManager;
        $this->container = $container;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\DI\Definition\Provider\DefinitionProviderInterface::getDefinition()
     */
    public function getDefinition(string $name)
    {
        switch ($name) {
            case ModuleSource::class:
                return new ObjectDefinition($name, $name);
            case ModuleParser::class:
                return new ObjectDefinition($name, $name);
            case StaticCopyer::class:
                return new ObjectDefinition($name, $name);
        }
        
        if (!$this->activateNamespaces) {
            return;
        }
        
        // search example module.tinyphp-ui
        if ($definition = $this->searchModuleDefinition($name)) {
            return $definition;
        }
        
        if ($definition = $this->searchDefinitionByNamespace($name)) {
            return $definition;
        }
    }
    
    /**
     * 设置已启用的命名空间对应的模块名称
     *
     * @param string $namespace
     * @param string $moduleName
     */
    public function setActivateModuleName($namespace, $moduleName)
    {
        $this->activateNamespaces[$namespace] = $moduleName;
    }
    
    /**
     * 通过命名空间获取对应的模块名称
     *
     * @param string $namespace
     * @return NULL
     */
    public function getActivateModuleName($namespace)
    {
        return key_exists($namespace, $this->activateNamespaces) ? $this->activateNamespaces[$namespace] : null;
    }
    
    /**
     * 获取模块的定义
     *
     * @param string $name
     * @return void|\Tiny\DI\Definition\ObjectDefinition
     * @example module.tinyphp-ui => new Module()
     */
    protected function searchModuleDefinition($name)
    {
        $matchs = [];
        if (!preg_match('/^' . str_replace('.', '\.', self::MODULE_CONTAINER_PREFIX) . '([a-z][a-z0-9\-_]+)$/', $name, $matchs)) {
            return;
        }
        $moduleName = $matchs[1];
        $module = $this->moduleManager->getModuleConfig($moduleName);
        if (!$module) {
            return;
        }
        return new ObjectDefinition($name, Module::class, [
            'mconfig' => $module
        ]);
    }
    
    /**
     * 获取与模块命名空间保持一致的类自动加载
     *
     * @param string $name
     * @return \Tiny\DI\Definition\ObjectDefinition
     */
    protected function searchDefinitionByNamespace($name)
    {
        foreach ($this->activateNamespaces as $namespace => $moduleName) {
            if (strpos($name, $namespace) !== 0) {
                continue;
            }
            
            $module = $this->moduleManager->getModuleConfig($moduleName);
            if (in_array($name, $module['namespace']['ignores'])) {
                continue;
            }
            
            // 注入模块实例
            $moduleInstance = $this->container->get(self::MODULE_CONTAINER_PREFIX . $moduleName);
            return new ObjectDefinition($name, $name, [
                ModuleManager::class => $this->moduleManager,
                Module::class => $moduleInstance,
                'moduleName' => $moduleName
            ]);
        }
    }
}
?>