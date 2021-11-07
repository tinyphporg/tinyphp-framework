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
 *          King 2021年10月19日下午2:40:02 stable 1.0.01 审定
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
class Smarty extends \Smarty implements IEngine
{
    /**
     * 当前的View对象
     * @var View
     */
    protected $_view;
    
    /**
     * 视图引擎配置
     *
     * @var array
     */
    protected $_viewEngineConfig = [];
    
    /**
     * 设置视图实例和初始化配置
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\IEngine::setView()
     */
    public function setViewEngineConfig(View $view, array $config)
    {
        $this->_view = $view;
        $this->_viewEngineConfig += $config;
    }
    
    /**
     * 设置缓存选项
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\IEngine::setCache()
     */
    public function setCache($cacheDir, int $cacheLifetime = 120)
    {
        $this->caching = ($cacheLifetime <= 0) ? FALSE : TRUE;
        $this->cache_dir = $cacheDir;
        $this->cache_lifetime = $cacheLifetime;
    }
}
?>