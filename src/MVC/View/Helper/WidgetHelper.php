<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Helper.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年10月25日下午5:14:14 0 第一次建立该文件
 *          King 2021年10月25日下午5:14:14 1 修改
 *          King 2021年10月25日下午5:14:14 stable 1.0 审定
 */
namespace Tiny\MVC\View\Helper;

use Tiny\MVC\View\Engine\Tagger\Parser\ParserInterface;
use Tiny\MVC\View\ViewManager;
use Tiny\MVC\Controller\Dispatcher;

/**
 * 视图助手的工具类 根据属性名检索视图层的所有助手并返回实例
 *
 * @package Tiny.MVC.View.Helper
 * @since 2021年10月25日下午5:14:14
 * @final 2021年10月25日下午5:14:14
 *       
 *       
 */
class WidgetHelper implements HelperInterface, ParserInterface
{
    
    /**
     * 部件默认ID
     *
     * @var string
     */
    const WIDGET_NAME = 'widget';
    
    /**
     * View 当前view实例
     *
     * @var ViewManager
     */
    protected $viewManager;
    
    /**
     * 配置
     *
     * @var array
     */
    protected $config;
    
    /**
     * 部件实例集合
     *
     * @var array
     */
    protected $widgets = [];
    
    /**
     * 构造函数
     *
     * @param array $config
     */
    public function __construct(ViewManager $viewManager, array $config = [])
    {
        $this->config = $config;
        $this->viewManager = $viewManager;
        
        // 获取部件的别名配置数组
        if (is_array($config['widgets'])) {
            $this->widgets = $config['widgets'];
        }
    }
    
    /**
     * 匹配部件的别名 作为视图助手实例存在
     *
     * @param string $hname
     */
    public function matchHelperName(string $widgetName)
    {
        // WidgetHelper 自身作为视图助手$view->widget存在
        if (self::WIDGET_NAME == $widgetName) {
            return $this;
        }
        
        // 部件的别名匹配查询
        foreach ($this->widgets as $widgetClass => $widgetConfig) {
            if (!$widgetConfig || !is_array($widgetConfig)) {
                continue;
            }
            
            // 匹配则返回视图部件实例
            if (in_array($widgetName, $widgetConfig)) {
                return $this->viewManager->getWidget($widgetClass);
            }
        }
    }
    
    /**
     * 解析Tagger的标签
     * {widget path="/main/index"} 默认部件 可将控制器动作函数直接引入解析模板
     * {widget.$tagName} 扩展各种小部件
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\View\Engine\Tagger\Parser\ParserInterface::onParseTag()
     */
    public function onParseTag($tagName, $namespace = '', array $params = [])
    {
        
        // 自身作为默认视图小部件 <:widget path=" " $id="xxx" />
        if ($namespace == '' && $tagName == 'widget') {
            return $this->parseWidgetTag($params);
        }
        
        // 命名空间需要<widget: />
        if (!self::WIDGET_NAME == $namespace) {
            return false;
        }
        
        foreach ($this->widgets as $widgetClass => $widgetAlias) {
            if (is_array($widgetAlias) && in_array($tagName, $widgetAlias)) {
                return $this->viewManager->getWidget($widgetClass)->parseTag($params);
            }
        }
        return false;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\View\Engine\Tagger\Parser\ParserInterface::onParseCloseTag()
     */
    public function onParseCloseTag($tagName, $namespace = '')
    {
        if ($namespace == '' && $tagName == 'widget') {
            return '';
        }
        return (self::WIDGET_NAME == $namespace) ? '' : false;
    }
    
    /**
     * 解析前
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\View\Engine\Tagger\Parser\ParserInterface::onPreParse()
     */
    public function onPreParse($template)
    {
        return false;
    }
    
    /**
     * 解析后
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\View\Engine\Tagger\Parser\ParserInterface::onPostParse()
     */
    public function onPostParse($template)
    {
        return false;
    }
    
    /**
     * 执行部件的渲染动作 控制器动作派发的组件
     *
     * @param string $path 派发路径 moudle:controller/action 类似ui:main/index
     * @param array $params 组件的参数数组
     * @return boolean|string
     */
    public function fetch($path, array $params = [])
    {
        $dispatcher = $this->viewManager->getOrCreateInstance(Dispatcher::class);
        if (!$dispatcher) {
            return false;
        }
        return $dispatcher->dispatchByPath($path, $params);
    }
    
    /**
     * 解析<:widget />标签为html
     *
     * @param array $params 参数数组
     * @return boolean|string
     */
    protected function parseWidgetTag(array $params)
    {
        $path = key_exists('path', $params) ? $params['path'] : ($params[1] ?: false);
        if (!$path) {
            return false;
        }
        // 
        $path = $params['path'];
        unset($params[0], $params['path'], $params[1]);
        $paramText = var_export($params, true);
        return sprintf("<?php echo \$view->widget->fetch(\"%s\", %s);?>", $path, $paramText);
    }
}
?>