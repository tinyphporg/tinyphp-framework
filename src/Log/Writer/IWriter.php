<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name IWriter.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-10上午06:14:13
 * @Description 日志写入器的接口
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-12-10上午06:14:13 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Log\Writer;

/**
 * 日志写入器的接口类
 *
 * @package Tiny.Log.Writer
 * @since 2013-12-10上午06:14:52
 * @final 2013-12-10上午06:14:52
 */
interface IWriter
{

    /**
     * 执行写入
     *
     * @param string $id
     *        错误日志的业务ID
     * @param string $msg
     *        错误信息
     * @param int $priority
     *        错误等级
     * @return bool
     */
    public function doWrite($id, $msg, $priority);
}
?>