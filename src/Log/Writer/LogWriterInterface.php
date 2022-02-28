<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name LogWriterInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午7:00:56
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午7:00:56 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Log\Writer;

/**
* 日志写入器接口
* 
* @package Tiny.Log.Writer
* @since 2022年2月12日下午7:01:22
* @final 2022年2月12日下午7:01:22
*/
interface  LogWriterInterface 
{
    /**
     * 执行日志写入
     *
     * @param string $id 日志ID
     * @param string $message 日志内容
     */
    public function write($logId, $message, $priority);
}
?>