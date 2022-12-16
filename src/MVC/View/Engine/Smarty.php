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
     * 支持匹配解析的扩展名文件
     *
     * @var array
     */
    protected $extendNames = ['tpl'];
    
    /**
     * 当前的View对象
     * 
     * @autowired
     * @var \Tiny\MVC\View\View
     */
    protected $view;
    
    /**
     * 增加匹配的扩展名
     *
     * @param string|array $extendName
     */
    public function addExtendName($extendName)
    {
        if (is_array($extendName)) {
            $this->extendNames = array_merge($this->extendNames, $extendName);
        } elseif(is_string($extendName)) {
            if (!in_array($extendName, $this->extendNames)) {
                $this->extendNames[] = $extendName;
            }
        }
        return false;
    }
    
    /**
     * 是否匹配对应的扩展名
     *
     * @param string $extendName 扩展名
     *
     * @return boolean true 匹配|false 不匹配
     */
    public function matchExtendName(string $extendName)
    {
        return in_array($extendName, $this->extendNames);
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