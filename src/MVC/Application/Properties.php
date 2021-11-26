<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name Properties.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月26日下午5:33:48 0 第一次建立该文件
 *          King 2021年11月26日下午5:33:48 1 修改
 *          King 2021年11月26日下午5:33:48 stable 1.0.01 审定
 */
namespace Tiny\MVC\Application;

use Tiny\Config\Configuration;

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