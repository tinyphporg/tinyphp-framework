<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Readonly.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月12日下午11:05:17
 * @Class List
 * @Function List
 * @History King 2017年3月12日下午11:05:17 0 第一次建立该文件
 *          King 2017年3月12日下午11:05:17 1 上午修改
 */
namespace Tiny\MVC\Request\Param;

/**
 * 只读参数实例
 *
 * @package Tiny.Application.Request.Param
 * @since 2017年3月12日下午11:06:27
 * @final 2017年3月12日下午11:06:27
 */
class Readonly extends Param
{
    
    /**
     * ArrayAccess set
     *
     * @param string $offset 键
     * @param mixed $value 值
     */
    public function offsetSet($offset, $value)
    {
        throw new ParamException("Param error: the Readonly param is not allow set it", E_ERROR);
    }
    
    /**
     * ArrayAccess unset
     *
     * @param string $offset 键
     */
    public function offsetUnset($offset)
    {
        throw new ParamException("Param error: the Readonly param is not allow unset it", E_ERROR);
    }
}
?>