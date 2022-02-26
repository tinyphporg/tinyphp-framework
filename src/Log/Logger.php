<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Logger.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-10上午02:27:22
 * @Description 日志记录者
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-12-10上午02:27:22 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Log;

use Tiny\Log\Writer\File;
use Tiny\Log\Writer\Syslog;
use Tiny\Log\Writer\Rsyslog;
use Tiny\Log\Writer\LogWriterInterface;

/**
 * 日志记录前端类
 *
 * @package Tiny.Log
 * @since 2013-12-10上午02:27:54
 * @final 2013-12-10上午02:27:54
 */
class Logger
{
    
    /**
     * 错误码与对应的错误标识
     *
     * @var array
     */
    const ERRORS = [
        E_NOTICE => 'E_NOTICE',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_WARNING => 'E_WARNING',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_USER_WARNING => 'E_USER_WARNING',
        E_ERROR => 'E_ERROR',
        E_USER_ERROR => 'E_USER_ERROR',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_STRICT => 'E_STRICT',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED'
    ];
    
    /**
     * 错误码对应的日志优先级
     *
     * @var array
     */
    const ERRORS_PRIORITIES = [
        E_NOTICE => 5,
        E_USER_NOTICE => 5,
        E_WARNING => 4,
        E_CORE_WARNING => 4,
        E_USER_WARNING => 4,
        E_ERROR => 3,
        E_USER_ERROR => 3,
        E_CORE_ERROR => 3,
        E_RECOVERABLE_ERROR => 3,
        E_STRICT => 3,
        E_DEPRECATED => 1,
        E_USER_DEPRECATED => 1
    ];
    
    /**
     * 日志优先级
     *
     * @var array
     */
    const PRIORITIES = [
        0 => 'Emergency',
        1 => 'Alert',
        2 => 'Critical',
        3 => 'Error',
        4 => 'Warning',
        5 => 'Notice',
        6 => 'Informational',
        7 => 'Debug'
    ];
    
    /**
     * 日志写入器的注册数组
     *
     * @var array
     */
    protected static $logWriterMap = [
        'file' => File::class,
        'syslog' => Syslog::class,
        'rsyslog' => Rsyslog::class
    ];
    
    /**
     * 日志写入器的数组
     *
     * @var array
     */
    protected $logWriters = [];
    
    /**
     * 注册日志写入器类
     *
     * @param string $writerId
     * @param string $writerClass
     * @throws LogException
     */
    public static function regLogWriter($writerId, $writerClass)
    {
        if (key_exists($writerId, self::$logWriterMap)) {
            throw new LogException(sprintf("Failed to register new log writer: the log wirter %s already exists!", $writerId));
        }
        self::$logWriterMap[$writerId] = $writerClass;
    }
    
    /**
     * 添加日志写入器
     *
     * @param string $writerId
     * @param mixed $config
     * @param int|array $priority
     * @throws LogException
     */
    public function addLogWriter($writerId, array $config = [], $priority = null)
    {
        if (!key_exists($writerId, self::$logWriterMap)) {
            throw new LogException(sprintf('Failed to add config for log writer :：log.writername:%s is not register!', $writerId));
        }
        
        $prioritys = [];
        if ($priority === null) {
            $prioritys = array_keys(self::PRIORITIES);
        } else 
            if (is_array($priority)) {
                foreach ($priority as $pi) {
                    if (key_exists($pi, self::PRIORITIES)) {
                        $prioritys[] = $pi;
                    }
                }
            } else 
                if (key_exists($priority, self::PRIORITIES)) {
                    $prioritys[] = $priority;
                }
        
        $this->logWriters[] = [
            'config' => $config,
            'writerId' => $writerId,
            'prioritys' => $prioritys,
            'instance' => null
        ];
    }
    
    /**
     * 日志记录
     *
     * @param string $id 日志ID
     * @param mixed $message 日志内容
     * @param $priority int 日志优先级别
     * @param array $extra 附加信息数组
     * @return void
     */
    public function log($id, $message, $priority = 1, $extra = [])
    {
        if (!key_exists($priority, self::PRIORITIES)) {
            $priority = 1;
        }
        if (is_object($message) || is_array($message)) {
            $message = var_export($message, true);
        }
        if (!empty($extra)) {
            $message .= var_export($extra, true);
        }
        $message = str_replace("\n", '', $message);
        $date = date('y-m-d H:i:s');
        $message = sprintf("%s %s %s \r\n", self::PRIORITIES[$priority], $date, $message);
        $this->write($id, $message, $priority);
    }
    
    /**
     * 记录错误信息
     *
     * @param int $errLevel 错误优先级别
     * @param mixed $message 日志内容
     * @param array $extra 附加信息数组
     * @return void
     */
    public function error($id, $errLevel, $message, $extra = [])
    {
        $errorid = self::ERRORS[$errLevel] ?: 'E_NOTICE';
        $priority = self::ERRORS_PRIORITIES[$errLevel] ?: 1;
        $message = $errorid . ' ' . $message;
        return $this->log($id, $message, $priority, $extra);
    }
    
    /**
     * 写入日志
     *
     * @param string $id 日志ID
     * @param string $messages
     * @param int $priority 日志优先等级
     * @return void
     */
    public function write($id, $message, $priority)
    {
        if (empty($this->logWriters)) {
            return;
        }
        foreach ($this->logWriters as &$w) {
            if (!in_array($priority, $w['prioritys'])) {
                continue;
            }
            if (!$w['instance']) {
                $w['instance'] = $this->createWriter($w['writerId'], $w['config']);
            }
            
            $w['instance']->write($id, $message, $priority);
        }
    }
    
    /**
     * 创建一个日志写入器
     *
     * @param string $writername
     * @param mixed $options
     * @throws LogException
     * @return LogWriterInterface
     */
    protected function createWriter(string $writerId, array $config = [])
    {
        $logWriteClass = self::$logWriterMap[$writerId];
        $logWriterInstance = new $logWriteClass($config);
        if (!$logWriterInstance instanceof LogWriterInterface) {
            throw new LogException(sprintf('Failed to instantiate logwriter: %s does not implement interface %s', $logWriteClass, LogWriterInterface::class));
        }
        return $logWriterInstance;
    }
}
?>