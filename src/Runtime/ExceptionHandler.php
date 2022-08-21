<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name IExceptionHandler.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午9:26:46
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午9:26:46 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Runtime;

use Tiny\Event\EventManager;
use Tiny\Event\Event;
use Tiny\Event\ExceptionEventListener;

// web下页面无法找到的错误定义
define('E_NOFOUND', 404);

/**
 * Runtime异常处理句柄
 *
 * @package Tiny.Runtime
 * @since : 2013-3-22上午06:15:37
 * @final : 2017-3-22上午06:15:37
 */
class ExceptionHandler implements ExceptionEventListener
{
    
    /**
     * code与名称映射表
     */
    const EXCEPTION_NAMES = [
        0 => 'Fatal error',
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSING ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_NOFOUND => 'NOT FOUND'
    ];
    
    /**
     * 需要抛出异常的错误集合
     */
    const THROW_EXCEPTIONS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR,
        E_NOFOUND,
        0
    ];
    
    /**
     * 所有异常情况集合
     */
    protected array $exceptions = [];
    
    /**
     * 事件管理器
     *
     * @var EventManager
     */
    protected $eventManager;
    
    /**
     * 注册异常捕获句柄
     *
     * @param EventManager $eventManager 事件管理器
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
        
        // @formatter:off
        set_exception_handler([$this, 'onThrowException']);
        set_error_handler([$this, 'onThrowError']);
        // @formatter:on
        
        // default exception Listener
        $eventManager->addEventListener($this);
    }
    
    /**
     * 默认的异常输出处理接口
     *
     * {@inheritdoc}
     * @see \Tiny\Event\ExceptionEventListener::onException()
     */
    public function onException(Event $event, \Throwable $exception, ExceptionHandler $handler)
    {
        // 配置异常通过日志方式输出
        echo "bbb";
        echo $exception->getTraceAsString();
        $code = $exception->getCode();
        if ($handler->isThrow($code)) {
            die();
        }
    }
    
    /**
     * 触发错误的事件处理函数
     *
     * @param int $errno 错误代码
     * @param string $errstr 错误内容
     * @param string $errfile 错误文件
     * @param int $errline 错误行
     */
    public function onThrowError($errno, $errstr, $errfile, $errline)
    {
        // 屏蔽通知和编码不严格警告
        if ($errno == E_NOTICE || $errno == E_STRICT) {
            return;
        }
        
        $exception = new \ErrorException($errstr, $errno, $errno, $errfile, $errline);
        $this->exceptions[] = $exception;
        
        // 触发onexception事件
        $event = new Event(Event::EVENT_ONEXCEPTION, [
            \Throwable::class => $exception,
            self::class => $this,
        ]);
        $this->eventManager->triggerEvent($event);
    }
    
    /**
     * 产生异常时调用的函数
     *
     *
     * @param \Exception $e 异常对象
     */
    public function onThrowException($exception)
    {
        $code = $exception->getCode();
        
        // 屏蔽不重要的
        if ($code == E_NOTICE || $code == E_STRICT) {
            return;
        }
        $this->exceptions[] = $exception;
        
        // onexception
        $event = new Event(Event::EVENT_ONEXCEPTION, [
            \Throwable::class => $exception,
            self::class => $this,
        ]);
        $this->eventManager->triggerEvent($event);
    }
    
    /**
     * 获取所有异常信息数组
     *
     * @param void
     * @return array
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }
    
    /**
     * 通过错误级别获取异常类型名
     *
     * @param int $level 错误级别
     * @return string
     */
    public function getExceptionName($level)
    {
        if (!key_exists($level, self::EXCEPTION_NAMES)) {
            $level = 0;
        }
        return self::EXCEPTION_NAMES[$level];
    }
    
    /**
     * 是否是需要抛出异常的错误级别
     *
     * @param int $errno 错误级别
     * @return bool
     */
    public function isThrow($errno)
    {
        return in_array($errno, self::THROW_EXCEPTIONS);
    }
}
?>