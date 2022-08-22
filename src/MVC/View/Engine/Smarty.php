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

use \Smarty as SmartyBase;
/**
 * smarty的模板引擎本地化扩展
 * 
 * @package Tiny.MVC.Viewer
 * @see composer require smarty:^3
 * @since 2021年10月19日下午2:40:02 
 * @final 2021年10月19日下午2:40:02 
 *
 */
class Smarty extends SmartyBase implements ViewEngineInterface
{
    /**
     * 当前的View对象
     * 
     * @autowired
     * @var \Tiny\MVC\View\View
     */
    protected $view;
    
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