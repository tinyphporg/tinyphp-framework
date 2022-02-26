<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Rsyslog.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月11日下午3:47:21
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月11日下午3:47:21 2017年3月8日下午4:20:28 0 第一次建立该文件
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
class Rsyslog implements LogWriterInterface
{
    
    /**
     * 日志所属的设备名称
     *
     * @var int 数值在0-23之间
     */
    protected $facility = 23;
    
    /**
     * 日志的严重程度 数值在0-7区间
     *
     * @var int
     */
    protected $severity = 6;
    
    /**
     * 服务器名称
     *
     * @var string a-zA-Z0-9
     */
    protected $hostname;
    
    /**
     * 服务名称
     *
     * @var string
     */
    protected $fqdn;
    
    /**
     * IP地址
     *
     * @var string
     */
    protected $ipFrom;
    
    /**
     * 进程名称
     *
     * @var string
     */
    protected $process;
    
    /**
     * 日志服务器地址
     *
     * @var string
     */
    protected $host = '127.0.0.1';
    
    /**
     * 日志服务器UDP端口
     *
     * @var int
     *
     */
    protected $port = 514;
    
    /**
     * UDP链接超时时间
     *
     * @var int
     *
     */
    protected $timeout = 1;
    
    /**
     * 构造函数 可输入策略数组，定义内容
     *
     *
     * @param array $config 配置数组
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->fqdn = $_SERVER['SERVER_ADDR'];
        $this->ipFrom = $_SERVER['SERVER_ADDR'];
        $this->process = 'TINYPHP' . getmypid();
        $this->hostname = gethostname();
        $host = (string)$config['host'];
        if ($host) {
            $this->host = $host;
        }
        
        $port = (int)$config['port'];
        if ($port > 0) {
            $this->port = $port;
        }
    }
    
    /**
     * 执行日志写入
     *
     * @param $id mixed 日志ID
     * @return bool
     *
     */
    public function write($id, $message, $severity)
    {
        if ($severity < 0) {
            $severity = 0;
        }
        if ($severity > 7) {
            $severity = 7;
        }
        
        $actualtime = time();
        $month = date("M", $actualtime);
        $day = substr("  " . date("j", $actualtime), -2);
        $hhmmss = date("H:i:s", $actualtime);
        $timestamp = $month . " " . $day . " " . $hhmmss;
        $pri = "<" . ($this->facility * 8 + $this->severity) . ">";
        $header = $timestamp . " " . $this->hostname;
        $message = $this->process . ": " . $this->fqdn . " " . $this->ipFrom . " " . $message;
        $message = substr($pri . $header . " " . $message, 0, 1024);
        $fp = fsockopen("udp://" . $this->host, $this->port, $severity, $message);
        if ($fp) {
            fwrite($fp, $message);
            fclose($fp);
            return true;
        }
        return false;
    }
}
?>