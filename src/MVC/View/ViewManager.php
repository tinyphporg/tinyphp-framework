<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ViewProvider.php
 * @author King
 * @version stable 2.0
 * @Date 2022年12月2日上午11:37:15
 * @Class List class
 * @Function List function_container
 * @History King 2022年12月2日上午11:37:15 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View;

use Tiny\DI\Definition\ObjectDefinition;
use Tiny\DI\Definition\DefinitionInterface;
use Tiny\DI\ContainerInterface;
use Tiny\MVC\View\Engine\PHP;
use Tiny\MVC\View\Engine\Smarty;
use Tiny\MVC\View\Engine\Markdown;
use Tiny\MVC\View\Engine\StaticFile;
use Tiny\DI\Container;
use Tiny\MVC\View\Engine\ViewEngineInterface;
use Tiny\MVC\View\Engine\Tagger\Tagger;
use Tiny\MVC\View\Helper\WidgetHelper;
use Tiny\MVC\View\Helper\HelperInterface;
use Tiny\MVC\View\Widget\WidgetInterface;

/**
 * 视图的容器定义源
 *
 * @package Tiny.MVC.View
 * @since 2022年12月2日上午11:38:00
 * @final 2022年12月2日上午11:38:00
 */
class ViewManager
{
    
    /**
     * 默认已经注册的视图渲染引擎
     * @formatter:off
     * 
     * @var array
     */
    const DEFAULT_ENGINE_LIST = [
        PHP::class => [ 'php'],
        Smarty::class => ['tpl'],
        Tagger::class => ['html', 'htm'],
        Markdown::class => ['md'],
        StaticFile::class => ['js', 'css', 'jpg', 'gif', 'png'],
    ]; // @formatter:on
    
    /**
     * 当前容器实例
     *
     * @var Container
     */
    protected $container;
    
    /**
     * 视图管理器实例
     *
     * @var View
     */
    protected $view;
    
    /**
     * 定义数组
     *
     * @var array
     */
    protected $definitions = [];
    
    /**
     * 注册的视图解析引擎列表
     *
     * @var array
     */
    protected $engines = [];
    
    /**
     * 注册的视图助手列表
     *
     * @var array
     */
    protected $helpers = [];
    
    /**
     * 注册的视图部件列表
     *
     * @var array
     */
    protected $widgets = [];
    
    /**
     * 视图部件的别名数组 用于 widgetHelper查找部件
     *
     * @var array
     */
    protected $widgetAlias = [];
    
    /**
     * 构造函数
     *
     * @param View $view
     */
    public function __construct(ContainerInterface $container, View $view)
    {
        $this->container = $container;
        $this->view = $view;
        
        // init engines
        foreach (self::DEFAULT_ENGINE_LIST as $engineClass => $extendNames) {
            $this->bindEngine($engineClass, $extendNames);
        }
        
        // 绑定视图部件助手
        $this->bindHelper(WidgetHelper::class, [
            'widgets' => &$this->widgetAlias
        ]);
    }
    
    /**
     * 获取或创建视图所需的类实例
     *
     * @param string $name
     * @param DefinitionInterface $definition
     */
    public function getOrCreateInstance(string $className, array $parameters = [])
    {
        if (!$this->container->has($className)) {
            $parameters[View::class] = $this->view;
            $parameters[ViewManager::class] = $this;
            $this->container->set($className, new ObjectDefinition($className, $className, $parameters));
        }
        return $this->container->get($className);
    }
    
    /**
     * 通过扩展名绑定视图处理引擎
     *
     * @param array $econfig
     * @return bool
     */
    public function bindEngine($engineClass, array $exts = [], array $config = [])
    {
        $engineInstance = null;
        if ($engineClass instanceof ViewEngineInterface) {
            $engineInstance = $engineClass;
            $engineClass = get_class($engineInstance);
        } elseif (!$engineClass || !is_string($engineClass)) {
            return false;
        }
        
        // 不存在新建
        if (!key_exists($engineClass, $this->engines)) {
            // @formatter:off
            $this->engines[$engineClass] = ['class' => $engineClass, 'config' => $config, 'exts' => $exts, 'instance' => $engineInstance];
            // @formatter:on
            return $this->engines[$engineClass];
        }
        
        // 引擎配置
        $engine = &$this->engines[$engineClass];
        $engine['class'] = $engineClass;
        
        // 存在类似配置 则合并
        if ($config) {
            $engine['config'] = array_merge_recursive($engine['config'], $config);
        }
        
        // 合并扩展名
        if ($exts) {
            array_map('strtolower', $exts);
            $engine['exts'] = array_merge($engine['exts'], $exts);
        }
        
        // 实例
        if ($engineInstance) {
            $engine['instance'] = $engineInstance;
        }
        return $engine;
    }
    
    /**
     * 通过模板的文件路径获取绑定的视图模板引擎实例
     *
     * @param string $filepath 模板文件路径
     * @return ViewEngineInterface
     */
    public function getEngineByPath(string $templateFile)
    {
        if (!$templateFile) {
            return false;
        }
        
        // 获取模板路径的文件扩展名
        $ext = pathinfo($templateFile, PATHINFO_EXTENSION);
        if (!$ext) {
            return false;
        }
        $engine = $this->getEnginebyExt($ext);
        if (!$engine) {
            throw new ViewException(sprintf('Viewer error:%s ext"' . $ext . '"is not bind', $templateFile));
        }
        return $engine;
    }
    
    /**
     * 根据模板路径的文件扩展名获取引擎配置
     *
     * @param string $ext 模板文件扩展名
     * @return array | void
     */
    public function getEngineByExt($ext)
    {
        // 扩展名向前覆盖
        $ext = strtolower($ext);
        $engines = array_reverse($this->engines);
        foreach ($engines as $engineConfig) {
            if (!in_array($ext, (array)$engineConfig['exts'])) {
                continue;
            }
            return $this->getEngine($engineConfig['class']);
        }
    }
    
    /**
     * 根据配置获取视图引擎实例
     *
     * @param array $econfig 视图引擎配置
     * @return ViewEngineInterface
     */
    public function getEngine(string $engineClass)
    {
        if (!key_exists($engineClass, $this->engines)) {
            return false;
        }
        
        $enginConfig = &$this->engines[$engineClass];
        if ($enginConfig['instance']) {
            return $enginConfig['instance'];
        }
        
        // instance
        $engineInstance = $this->getOrCreateInstance($engineClass, [
            'config' => (array)$enginConfig['config']
        ]);
        
        if (!$engineInstance instanceof ViewEngineInterface) {
            throw new ViewException(sprintf('class "%s" is not instanceof \Tiny\MVC\View\Engine\IEngine', $engineClass));
        }
        
        $enginConfig['instance'] = $engineInstance;
        $this->syncAssign([], $engineClass);
        $this->syncCompileDir(null, $engineClass);
        $this->syncTemplateDir(null, $engineClass);
        return $engineInstance;
    }
    
    /**
     * 通过扩展名绑定视图助手以备惰性加载
     *
     * @param mixed $hconfig 助手配置
     * @return bool
     */
    public function bindHelper($helperClass, array $config = [])
    {
        $helperInstance = null;
        if ($helperClass instanceof HelperInterface) {
            $helperInstance = $helperClass;
            $helperClass = get_class($helperInstance);
        } elseif (!$helperClass || !is_string($helperClass)) {
            return false;
        }
        
        // 不存在新建
        if (!key_exists($helperClass, $this->helpers)) {
            $this->helpers[$helperClass] = [
                'class' => $helperClass,
                'instance' => $helperInstance,
                'config' => $config
            ];
            return $this->helpers[$helperClass];
        }
        
        // 存在则合并
        $helper = &$this->helpers[$helperClass];
        $helper['class'] = $helperClass;
        
        // 递归合并相同配置
        if ($config) {
            $helper['config'] = array_merge_recursive($helper['config'], $config);
        }
        
        if ($helperInstance) {
            $helper['instance'] = $helperInstance;
        }
        return $helper;
    }
    
    /**
     * 根据类名获取视图助手实例
     *
     * @param string $helperClass 视图助手的类名
     * @throws ViewException
     * @return false 获取失败
     *          HelperInterface 视图助手实例
     */
    public function getHelper(string $helperClass)
    {
        if (!key_exists($helperClass, $this->helpers)) {
            return false;
        }
        
        $helperConfig = &$this->helpers[$helperClass];
        if ($helperConfig['instance']) {
            return $helperConfig['instance'];
        }
        $helperInstance = $this->getOrCreateInstance($helperClass, [
            'config' => $helperConfig['config']
        ]);
        if (!$helperInstance instanceof HelperInterface) {
            throw new ViewException(sprintf('class "%s" is not instanceof %s', $helperClass, HelperInterface::class));
        }
        
        //
        $helperConfig['instance'] = $helperInstance;
        return $helperInstance;
    }
    
    /**
     * 获取匹配的助手实例
     *
     * @param string $helperName
     * @return HelperInterface
     */
    public function matchHelper(string $helperName)
    {
        // 助手名必须以字母开头
        if (!preg_match("/[a-z][a-z0-9_]+/i", $helperName)) {
            return;
        }
        
        // 倒序查找助手配置
        $helperClasses = array_reverse(array_keys($this->helpers));
        foreach ($helperClasses as $helperClass) {
            $helperInstance = $this->getHelper($helperClass);
            if (!$helperInstance) {
                continue;
            }
            
            // 匹配助手名
            $matchRet = $helperInstance->matchHelperName($helperName);
            if ($matchRet) {
                return $matchRet;
            }
        }
    }
    
    /**
     * 绑定一个视图部件
     * 
     * @param string $widgetClass 视图部件类名
     *        WidgetInterface  $widgetClass 视图部件实例
     * @param array $config 视图部件初始化的配置数组
     * @param string $alias 通过widgetHelper检索视图部件的别名数组
     *        为空时 默认为小写的、不包含命名空间的类名 
     * @return boolean 绑定失败
     *         array 绑定后的视图部件配置数组
     */
    public function bindWidget($widgetClass, array $config = [], string $alias = '')
    {
        // $widgetClass 为视图部件实例 
        $widgetInstance = null;
        if ($widgetClass instanceof WidgetInterface) {
            $widgetInstance = $widgetClass;
            $widgetClass = get_class($widgetInstance);
        } elseif (!$widgetClass || !is_string($widgetClass)) {
            return false;
        }
        
        // 类名(不包含命名空间) 作为view部件检索的别名存在
        $widgetName = strtolower(basename(str_replace('\\', '/', $widgetClass)));
       
        // 不存在则新建
        if (!key_exists($widgetClass, $this->widgets)) {
            $this->widgets[$widgetClass] = [
                'class' => $widgetClass,
                'instance' => $widgetInstance,
                'config' => $config
            ];
            $this->widgetAlias[$widgetClass] = [
                $widgetName
            ];
            
            if ($alias) {
                $this->widgetAlias[$widgetClass][] =  $alias;
            }
            return $this->widgets[$widgetClass];
        }
        
        // 存在则修改
        $widget =  $this->widgets[$widgetClass];
        $widget['class'] = $widgetClass;
        if ($config) {
            $widget['config'] = array_merge_recursive($widget['config'], $config);
        }
        
        if ($alias && !in_array($alias, $this->widgetAlias[$widgetClass])) {
            $this->widgetAlias[$widgetClass][] = $alias;
        }
        
        if ($widgetInstance) {
            $widget['instance'] = $widgetInstance;
        }
        return $widget;
    }
    
    /**
     * 获取部件实例
     *
     * @param string $widgetClass 部件类名
     * @throws ViewException
     * @return boolean|WidgetInterface
     */
    public function getWidget(string $widgetClass)
    {
        if (!key_exists($widgetClass, $this->widgets)) {
            return false;
        }
        
        // 读取配置
        $widgetConfig = &$this->widgets[$widgetClass];
        if ($widgetConfig['instance']) {
            return $widgetConfig['instance'];
        }
        
        // 创建实例
        $widgetInstance = $this->getOrCreateInstance($widgetClass, [
            'config' => (array)$widgetConfig['config']
        ]);
        if (!$widgetInstance instanceof WidgetInterface) {
            throw new ViewException(sprintf('class "%s" is not instanceof %s', $widgetClass, WidgetInterface::class));
        }
        
        // 存放
        $widgetConfig['instance'] = $widgetInstance;
        return $widgetInstance;
    }
    
    /**
     * 获取视图部件的视图助手实例
     * 
     * @return WidgetHelper
     */
    public function getWidgetHelper()
    {
        return $this->getHelper(WidgetHelper::class);
    }
    
    /**
     * 同步预编译的变量
     *
     * @param array $assign
     */
    public function syncAssign(array $assign = [], string $engineClass = '')
    {
        if (!$assign) {
            $variables = $this->view->getVariables();
        }
        foreach ($this->engines as $engine) {
            if ($engineClass && $engineClass != $engine['class']) {
                continue;
            }
            if ($engine['instance']) {
                $engine['instance']->assign($variables);
            }
        }
    }
    
    /**
     * 同步模板存放路径
     *
     * @param mixed $dirname
     */
    public function syncTemplateDir($dirname = null, string $engineClass = '')
    {
        if (!$dirname) {
            $dirname = $this->view->getTemplateDir();
        }
        
        // 同步所有已经实例化的视图引擎实例
        foreach ($this->engines as $engine) {
            if ($engineClass && $engineClass != $engine['class']) {
                continue;
            }
            if ($engine['instance']) {
                $engine['instance']->setTemplateDir($dirname);
            }
        }
    }
    
    /**
     * 同步模板的编译路径
     *
     * @param mixed $dirname 编译文件夹路径
     */
    public function syncCompileDir($dirname = null, string $engineClass = '')
    {
        if (!$dirname) {
            $dirname = $this->view->getCompileDir();
        }
        
        // 同步给所有实例化的引擎
        foreach ($this->engines as $engine) {
            if ($engineClass && $engineClass != $engine['class']) {
                continue;
            }
            if ($engine['instance']) {
                $engine['instance']->setCompileDir($dirname);
            }
        }
    }
}
?>