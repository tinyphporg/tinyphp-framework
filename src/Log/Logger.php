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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Log;

use Tiny\Log\Writer\IWriter;

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
    protected static $_writerMap = array(
        'file' => '\Tiny\Log\Writer\File',
        'syslog' => '\Tiny\Log\Writer\Syslog',
        'rsyslog' => '\Tiny\Log\Writer\Rsyslog'
    );

    /**
     * 单一实例
     *
     * @var Logger
     */
    protected static $_instance;

    /**
     * 日志写入器的数组
     *
     * @var array
     */
    protected $_writers = [];

    /**
     * 获取Logger的单一实例
     *
     * @return Logger
     */
    public static function getInstance()
    {
        if (!self::$_instance)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 注册日志写入器类型
     *
     * @param string $type
     *        日志写入器类型
     * @param string $className
     *        日志写入器的类名
     * @return void
     */
    public static function regLogWriter($name, $className)
    {
        if (key_exists($name, self::$_writerMap))
        {
            throw new LogException(sprintf("注册日志写入器失败:type:%s已经存在", $name));
        }
        self::$_writerMap[$name] = $className;
    }

    /**
     * 添加日志的写入代理
     *
     * @param string $writername
     *        写入代理名称
     * @param array $options
     *        代理参数
     * @param $priority int
     *        指定的日志级别 可以为数组
     * @return void
     *
     */
    public function addWriter($writername, $policy, $priority = NULL)
    {
        if (!key_exists($writername, self::$_writerMap))
        {
            throw new LogException(sprintf('添加日志的写入器失败：writername:%s没有配置', $writername));
        }

        if (!is_array($priority) && key_exists($priority, self::PRIORITIES))
        {
            $priority = [
                (int)$priority
            ];
        }

        if (!$priority)
        {
            $priority = array_keys(self::PRIORITIES);
        }

        $this->_writers[] = [
            'policy' => $policy,
            'writername' => $writername,
            'priority' => $priority,
            'instance' => NULL
        ];
    }

    /**
     * 日志记录
     *
     * @param string $id
     *        日志ID
     * @param mixed $message
     *        日志内容
     * @param $priority int
     *        日志优先级别
     * @param array $extra
     *        附加信息数组
     * @return void
     */
    public function log($id, $message, $priority = 1, $extra = [])
    {
        if (!key_exists($priority, self::PRIORITIES))
        {
            $priority = 1;
        }
        if (is_object($message) || is_array($message))
        {
            $message = var_export($message, TRUE);
        }
        if (!empty($extra))
        {
            $message .= var_export($extra, TRUE);
        }
        $message = str_replace("\n", '', $message);
        $date = date('y-m-d H:i:s');
        $message = sprintf("%s %s %s \r\n", self::PRIORITIES[$priority], $date, $message);
        $this->write($id, $message, $priority);
    }

    /**
     * 记录错误信息
     *
     * @param int $errLevel
     *        错误优先级别
     * @param mixed $message
     *        日志内容
     * @param array $extra
     *        附加信息数组
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
     * @param string $id
     *        日志ID
     * @param string $messages
     * @param int $priority
     *        日志优先等级
     * @return void
     */
    public function write($id, $message, $priority)
    {
        if (empty($this->_writers))
        {
            return;
        }
        foreach ($this->_writers as & $w)
        {
            if (!in_array($priority, $w['priority']))
            {
                continue;
            }
            if (!$w['instance'])
            {
                $w['instance'] = $this->_createWriter($w['writername'], $w['policy']);
            }

            $w['instance']->doWrite($id, $message, $priority);
        }
    }

    /**
     * 创建一个日志写入器
     *
     * @param string $writername
     * @param mixed $options
     * @throws LogException
     * @return \Tiny\Log\Writer\IWriter
     */
    protected function _createWriter($writername, $policy)
    {
        $className = self::$_writerMap[$writername];
        $writer = new $className($policy);
        if (!($writer instanceof IWriter))
        {
            throw new LogException('实例化LogWriter失败：没有实现接口Tiny\Log\Writer\IWriter');
        }
        return $writer;
    }

    /**
     * 构造函数 限制为单例模式
     *
     * @return void
     */
    protected function __construct()
    {
    }
}
?>