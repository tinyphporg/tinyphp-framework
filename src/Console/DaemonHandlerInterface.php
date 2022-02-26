<?php
/**
 * @Copyright (C), 2013-, King.
 * @Name DaemonHandlerInterface.php
 * @Author King
 * @Version Beta 1.0
 * @Date 2020年4月17日下午6:05:19
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年4月17日下午6:05:19 第一次建立该文件
 *                 King 2020年4月17日下午6:05:19 修改
 *                 King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\Console;

/**
 * 守护主进程委托事件的句柄接口
 *
 * @package Tiny.Console
 * @since 2020年4月17日下午6:06:56
 * @final 2020年4月17日下午6:06:56
 */
interface DaemonHandlerInterface
{
    /**
     * 输出日志
     * @param string $logId
     * @param string $log
     */
    public function onOutLog($logId, $log, $priority = 1);
}
?>