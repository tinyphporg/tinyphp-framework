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
namespace Tiny\MVC\Viewer;

/**
 * smarty的模板引擎本地化扩展
 * 
 * @package Tiny.MVC.Viewer
 * @see composer require smarty:^3
 * @since 2021年10月19日下午2:40:02 
 * @final 2021年10月19日下午2:40:02 
 *
 */
class Smarty extends \Smarty
{

    public function setTemplateFolder($path)
    {
        $this->template_dir = $path;
    }

    public function setCompileFolder($path)
    {
        $this->compile_dir = $path;
    }

    public function setCache($cacheDir, int $cacheLifetime = 120)
    {
        $this->caching = ($cacheLifetime <= 0) ? FALSE : TRUE;
        $this->cache_dir = $cacheDir;
        $this->cache_lifetime = $cacheLifetime;
    }
}
?>