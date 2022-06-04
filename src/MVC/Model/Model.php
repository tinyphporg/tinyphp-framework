<?php
/**
 * 模型基类
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月3日下午2:48:51
 * @Class List
 * @Function List
 * @History King 2017年4月3日下午2:48:51 0 第一次建立该文件
 *          King 2017年4月3日下午2:48:51 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Model;

use Tiny\Data\Data;
use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\Module\Module;

/**
 * 模型基类
 *
 * @package Tiny.Application.Model
 * @since 2017年4月3日下午2:49:43
 * @final 2017年4月3日下午2:49:43
 */
abstract class Model
{
    
    /**
     * 当前应用实例
     *
     * @autowired
     * @var ApplicationBase
     */
    protected ApplicationBase $app;
    
    /**
     * 数据池实例
     *
     * @autowired
     * 
     * @var Data
     */
    protected Data $data;
    
    /**
     * @autowired
     * 
     * @var Module
     */
    protected Module $module;
    /**
     * 数据ID
     *
     * @var string
     */
    protected $dataId = 'default';
    
    /**
     * 写入日志
     *
     * @param string $id
     * @return bool
     */
    public function log($id, $message, $priority = 1, $extra = [])
    {
         return $this->app->getLogger()->log($id, $message, $priority, $extra);
    }
}
?>