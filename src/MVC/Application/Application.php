<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name Application.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月26日下午6:38:24 0 第一次建立该文件
 *          King 2021年11月26日下午6:38:24 1 修改
 *          King 2021年11月26日下午6:38:24 stable 1.0.01 审定
 */
namespace Tiny\MVC\Application;


use Tiny\Config\Configuration;

/**
* application属性
* 
* @package Tiny.MVC.Application
* @since 2021年11月27日 下午1:01:32
* @final 2021年11月27日下午1:01:32
*/
class Properties extends Configuration
{
    public function get($node = null)
    {
        $data  = parent::get($node);
        return $this->validConfig($node, $data);
    }
    
    protected function validConfig($node, $data)
    {
        switch ($node)
        {
            case 'cache':
                return $this->validCacheConfig($data);
        }
        return $data;
    }
}

?>