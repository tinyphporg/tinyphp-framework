<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Rsyslog.php
 * @author King
 * @version 1.0
 * @Date: 2014-2-4下午06:15:06
 * @Description
 * @Class List
 *        1.
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King 2014-2-4下午06:15:06 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Log\Writer;

/**
 * 远程日志类
 * Facility values:
 * 0 kernel messages
 * 1 user-level messages
 * 2 mail system
 * 3 system daemons
 * 4 security/authorization messages
 * 5 messages generated internally by syslogd
 * 6 line printer subsystem
 * 7 network news subsystem
 * 8 UUCP subsystem
 * 9 clock daemon
 * 10 security/authorization messages
 * 11 FTP daemon
 * 12 NTP subsystem
 * 13 log audit
 * 14 log alert
 * 15 clock daemon
 * 16 local user 0 (local0) (default value)
 * 17 local user 1 (local1)
 * 18 local user 2 (local2)
 * 19 local user 3 (local3)
 * 20 local user 4 (local4)
 * 21 local user 5 (local5)
 * 22 local user 6 (local6)
 * 23 local user 7 (local7)
 * Severity values:
 * 0 Emergency: system is unusable
 * 1 Alert: action must be taken immediately
 * 2 Critical: critical conditions
 * 3 Error: error conditions
 * 4 Warning: warning conditions
 * 5 Notice: normal but significant condition (default value)
 * 6 Informational: informational messages
 * 7 Debug: debug-level messages
 *
 * @package RPC.Log
 * @since 2014-1-30下午03:19:34
 * @final 2014-1-30下午03:19:34
 * @example
 *
 *
 */
class Rsyslog implements IWriter
{

    /**
     * 日志所属的设备名称
     *
     * @var int 数值在0-23之间
     */
    protected $_facility = 23;

    /**
     * 日志的严重程度 数值在0-7区间
     *
     * @var int
     */
    protected $_severity = 6;

    /**
     * 服务器名称
     *
     * @var string a-zA-Z0-9
     */
    protected $_hostname = 'ZEROAI-PHP-SERVER';

    /**
     * 服务名称
     *
     * @var string
     */
    protected $_fqdn;

    /**
     * IP地址
     *
     * @var string
     */
    protected $_ipFrom;

    /**
     * 进程名称
     *
     * @var string
     */
    protected $_process;

    /**
     * 日志服务器地址
     *
     * @var string
     */
    protected $_host;

    /**
     * 日志服务器UDP端口
     *
     * @var int
     *
     */
    protected $_port;

    /**
     * UDP链接超时时间
     *
     * @var int
     *
     */
    protected $_timeout = 1;

    /**
     * 构造函数 可输入策略数组，定义内容
     *
     *
     * @param array $policy 配置数组
     * @return void
     */
    public function __construct(array $policy = [])
    {
        $this->_fqdn = $_SERVER['SERVER_ADDR'];
        $this->_ipFrom = $_SERVER['SERVER_ADDR'];
        $this->_process = 'PHP' . getmypid();
        $this->_host = $policy['host'] ?: '127.0.0.1';
        $this->_port = (int)$policy['port'] ?: 514;
    }

    /**
     * 执行日志写入
     *
     * @param $id mixed
     *        日志ID
     * @return bool
     *
     */
    public function doWrite($id, $message, $severity)
    {
        if ($severity < 0)
        {
            $severity = 0;
        }
        if ($severity > 7)
        {
            $severity = 7;
        }
        $actualtime = time();
        $month = date("M", $actualtime);
        $day = substr("  " . date("j", $actualtime), -2);
        $hhmmss = date("H:i:s", $actualtime);
        $timestamp = $month . " " . $day . " " . $hhmmss;
        $pri = "<" . ($this->_facility * 8 + $this->_severity) . ">";
        $header = $timestamp . " " . $this->_hostname;
        $message = $this->_process . ": " . $this->_fqdn . " " . $this->_ipFrom . " " . $message;
        $message = substr($pri . $header . " " . $message, 0, 1024);
        $fp = fsockopen("udp://" . $this->_host, $this->_port, $severity, $message);
        if ($fp)
        {
            fwrite($fp, $message);
            fclose($fp);
            return TRUE;
        }
        return FALSE;
    }
}
?>