<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Smarty.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年10月19日下午2:40:02 0 第一次建立该文件
 *          King 2021年10月19日下午2:40:02 1 修改
 *          King 2021年10月19日下午2:40:02 stable 1.0 审定
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\View;

/**
 * smarty的模板引擎本地化扩展
 * 
 * @package Tiny.MVC.Viewer
 * @see composer require smarty:^3
 * @since 2021年10月19日下午2:40:02 
 * @final 2021年10月19日下午2:40:02 
 *
 */
class Smarty extends \Smarty implements ViewEngineInterface
{
    /**
     * 当前的View对象
     * @var View
     */
    protected $view;
    
    /**
     * 视图引擎配置
     *
     * @var array
     */
    protected $viewEngineConfig = [];
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\ViewEngineInterface::setViewEngineConfig()
     */
    public function setViewEngineConfig(View $view, array $config)
    {
        $this->view = $view;
        $this->viewEngineConfig += $config;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\ViewEngineInterface::setCache()
     */
    public function setCache($cacheDir, int $cacheLifetime = 120)
    {
        $this->caching = ($cacheLifetime <= 0) ? false : true;
        $this->cache_dir = $cacheDir;
        $this->cache_lifetime = $cacheLifetime;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\ViewEngineInterface::fetch()
     */
    public function fetch($template = null, $assigns = null, $compileId = null, $cacheId = null)
    {
        $this->view->addTemplateList($template, $template, $this);
        return parent::fetch($template, $cacheId, $compileId, $assigns);
    }
}
?>