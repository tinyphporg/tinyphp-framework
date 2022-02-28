<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Syslog.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月11日下午3:47:21
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月11日下午3:47:21 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Log\Writer;

/**
 * 系统syslog写入器
 *
 * @package Tiny.Log.Writer
 * @since 2013-12-10上午11:38:18
 * @final 2013-12-10上午11:38:18
 */
class Syslog implements LogWriterInterface
{
    
    /**
     * 构造函数
     *
     * @param array $config 配置数组
     */
    public function __construct(array $config = [])
    {
    }
    
    /**
     * 执行日志写入
     *
     * @param string $id 日志ID
     * @param string $message 日志内容
     */
    public function write($logId, $message, $priority)
    {
        syslog($priority, $logId . ' ' . $message);
    }
}
?>