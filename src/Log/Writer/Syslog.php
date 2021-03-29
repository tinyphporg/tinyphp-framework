<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Syslog.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-10上午10:31:15
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-12-10上午10:31:15 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Log\Writer;


/**
 * 系统syslog写入器
 *
 * @package Tiny.Log.Writer
 * @since 2013-12-10上午11:38:18
 * @final 2013-12-10上午11:38:18
 */
class Syslog implements IWriter
{

    /**
     * 默认的策略数组
     *
     * @var array
     */
    protected $_policy = [];

    /**
     * 构造函数
     *
     * @param array $policy
     *        策略数组
     * @return void
     */
    public function __construct(array $policy = [])
    {
        $this->_policy = array_merge($this->_policy, $policy);
    }

    /**
     * 执行日志写入
     *
     * @param string $id
     *        日志ID
     * @param string $message
     *        日志内容
     * @return void
     */
    public function doWrite($id, $message, $priority)
    {
        syslog($priority, $id . ' ' . $message);
    }
}
?>