<?php

/**
 *
 * @copyright (C), 2013-, King.
 * @name Runtime.php
 * @author King
 * @version Beta 1.0
 * @Date: 2019年11月12日上午10:07:58
 * @Description 运行时库
 * @Class List
 *        1.RuntimeException Runtime异常类
 *        2.Runtime 运行时类
 *        3.Autoloader 自动加载类
 *        4.ICacheHandler runtime缓存接口
 *
 * @Function List 1.
 * @History King 2019年11月12日上午10:07:58 第一次建立该文件
 *          King 2020年02月19日下午15:44:00 stable 1.0.01 审定稳定版本
 *
 */
namespace Tiny\Runtime;

use Tiny\MVC\ApplicationBase;

/* 定义框架所在路径 */
define('FRAMEWORK_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
/**
 * Runtime缓存接口
 *
 * @package Tiny.Runtime
 * @since 2019年11月12日上午10:07:58
 * @final 2020年02月19日下午15:44:00 stable 1.0.01 审定
 *
 */
interface ICacheHandler
{

    /**
     *
     * @param mixed $data
     *        执行存取缓存数据的动作
     */
    public function oncache($data = NULL);
}

/**
 * 运行时异常类
 *
 * @package Tiny.Runtime
 * @since 2019年11月18日下午3:22:53
 * @final 2019年11月18日下午3:22:53
 */
class RuntimeException extends \Exception
{
}

/**
 * 运行时主体类
 *
 * @package Runtime
 * @since 2019年11月12日上午10:11:41
 * @final 2019年11月12日上午10:11:41
 */
class Runtime
{

    /**
     * 框架名称
     *
     * @var string
     */
    const FRAMEWORK_NAME = 'Tiny Framework For PHP';

    /**
     * 框架版本号
     *
     * @var string
     */
    const FRAMEWORK_VERSION = '1.0.01 stable';

    /**
     * 框架所在目录
     *
     * @var string
     */
    const FRAMEWORK_PATH = FRAMEWORK_PATH;

    /**
     * WEB模式
     *
     * @var integer
     */
    const RUNTIME_MODE_WEB = 0;

    /**
     * 命令行模式
     *
     * @var integer
     */
    const RUNTIME_MODE_CONSOLE = 1;

    /**
     * RPC模式
     *
     * @var integer
     */
    const RUNTIME_MODE_RPC = 2;

    /**
     * 环境参数类
     *
     * @var Environment 环境参数类
     */
    public $env;

    /**
     * 单例
     *
     * @var Runtime
     */
    protected static $_instance;

    /**
     * app策略集合
     *
     * @var array 运行时模式对应创建的application具体对象
     *      WEB模式 | CONSOLE模式 | RPC模式
     */
    protected static $_appMap = [
        self::RUNTIME_MODE_CONSOLE => '\Tiny\MVC\ConsoleApplication', /*mode_console*/
        self::RUNTIME_MODE_WEB => '\Tiny\MVC\WebApplication', /*mode__web*/
        self::RUNTIME_MODE_RPC => '\Tiny\MVC\RPCApplication' /* mode_rpc */
    ];

    /**
     * 运行时创建的应用程序实例
     *
     * @var ApplicationBase
     */
    protected $_app;

    /**
     * 运行时创建的自动加载对象实例
     *
     * @var Autoloader
     */
    protected $_autoloader;

    /**
     * 运行时创建的异常处理实例
     *
     * @var ExceptionHandler
     */
    protected $_exceptionHandler;

    /**
     * 注册或者替换已有的Application
     *
     * @param int $mode
     * @param string $className
     * @return bool TRUE success || TRUE falid
     */
    public static function regApplicationMap($mode, $className): bool
    {
        if (!$className instanceof ApplicationBase)
        {
            return FALSE;
        }
        self::$_appMap[$mode] = $className;
        return TRUE;
    }

    /**
     *
     * @获取单例实例
     *
     * @return Runtime
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
     * 根据运行环境创建应用实例
     *
     * @param $apppath string
     *        app目录所在路径
     * @param $profile string
     *        应用实例的配置路径
     * @return ApplicationBase 当前应用实例
     * @example \Zeroai\Tiny::createApplication($apppath, $profile);
     *          Web \Tiny\Web\Application
     *          Console \Tiny\Console\Application
     *          RPC \Tiny\Rpc\Application
     */
    public function createApplication($appPath, $profile = NULL)
    {
        if (!$this->_app)
        {
            $className = self::$_appMap[$this->env['RUNTIME_MODE']];
            $this->_app = new $className($appPath, $profile);
        }
        return $this->_app;
    }

    /**
     * 导入自动加载的类库
     *
     * @param string $path
     *        类库加载绝对路径
     * @param string $namespace
     *        命名空间
     * @return false || true
     */
    public function import($path, $namespace = NULL)
    {
        return $this->_autoloader->add($path, $namespace);
    }

    /**
     * 获取所加载的库和路径
     * @return array
     */
    public function getImports()
    {
        return $this->_autoloader->getImports();
    }

    /**
     * 注册运行时异常处理句柄
     *
     * @param
     *        IExceptionHandler 错误处理句柄接口
     * @return bool
     */
    public function regExceptionHandler(IExceptionHandler $handler)
    {
        return $this->_exceptionHandler->regExceptionHandler($handler);
    }

    /**
     * 构建基本运行环境所需的各种类
     *
     * @return void
     */
    protected function __construct()
    {
        $this->env = Environment::getInstance();

        // 创建运行时的自动加载实例
        $this->_autoloader = Autoloader::getInstance();
        $this->_autoloader->add(self::FRAMEWORK_PATH, 'Tiny');

        // $this->_autoloader
        $this->_exceptionHandler = ExceptionHandler::getInstance();
    }
}

/**
 * 自动加载基类
 *
 * @package Tiny\Runtime
 * @since 2019年11月12日上午10:15:05
 * @final 2019年11月12日上午10:15:05
 */
class Autoloader
{

    /**
     * 单例
     *
     * @var Runtime
     */
    protected static $_instance;

    /**
     * 库的缓存数组
     *
     * @var array
     */
    protected $_libs = [];

    /**
     * 加载路径的数组
     *
     * @var array
     */
    protected $_paths;

    /**
     *
     * @获取单例实例
     *
     * @return Autoloader
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
     * 添加组件库
     *
     * @param string $path
     *        组件库路径 为*时，添加全局类
     * @param string $prefix
     *        命名空间名称
     * @return void
     */
    public function add($path, $namespace = NULL)
    {
        if (!$namespace)
        {
            $namespace = basename($path);
        }
        elseif ('*' == $namespace)
        {
            if($path[strlen($path) - 1] == "/")
            {
                $path = substr($path, 0, strlen($path) - 1);
            }
            set_include_path(get_include_path() . PATH_SEPARATOR . $path);
            return;
        }
        if (!key_exists($namespace, $this->_libs))
        {
            $this->_libs[$namespace] = [];
        }
        if (in_array($path, $this->_libs[$namespace]))
        {
            return;
        }

        $this->_libs[$namespace][] = $path;
    }

    public function getImports()
    {
        return $this->_libs;
    }
    /**
     * 根据类名加载文件
     *
     * @param string $className
     *        类名
     * @return bool
     */
    public function load($cname)
    {
        //echo $cname . '|' . strrpos($cname, "\\") . "\n";
        if (FALSE === strpos($cname, "\\"))
        {
            $ipath =  $cname . '.php';
            include $ipath;
            return;
        }
        $searchParams = [];
        $params = explode("\\", $cname);
        for ($i = count($params); $i >= 1; $i--)
        {
            $searchParams[] = [
                join("\\", array_slice($params, 0, $i)),
                join('/', array_slice($params, $i))
            ];
        }
        foreach ($searchParams as $sp)
        {
            if ($this->_loadFromPath($sp[0], $sp[1], $cname))
            {
                break;
            }
        }
    }

    /**
     * 寻找和加载类路径
     *
     * @param string $namespace
     *        寻找的命名空间
     * @param string $pathSuffix
     *        路径尾缀
     * @param string $cname
     *        类名
     * @return bool false加载失败 || true成功
     */
    protected function _loadFromPath($namespace, $pathSuffix, $cname)
    {
        if (!$this->_libs[$namespace])
        {
            return FALSE;
        }
        $paths = $this->_libs[$namespace];
        foreach ($paths as $ipath)
        {
            $ipath = $ipath . $pathSuffix . '.php';
            if (!is_file($ipath))
            {
                if (!$pathSuffix)
                {
                    return FALSE;
                }
                $parentDir = dirname($ipath);
                $ipath = $parentDir . DIRECTORY_SEPARATOR . basename($parentDir) . '.php';
                if (!is_file($ipath))
                {
                    return FALSE;
                }
            }
            include_once ($ipath);
        }
        return FALSE;
    }

    /**
     * 构造函数，主动自动加载类
     *
     * @param
     *        void
     * @return void
     */
    protected function __construct()
    {
        spl_autoload_register([
            $this,
            'load'
        ]);
    }
}

/**
 * 当前运行时(Runtime)的环境和平台参数。此类不能被继承。Readonly
 *
 * @package Tiny.Runtime
 * @since 2013-3-30下午12:27:47
 * @final 2013-11-26下午
 */
class Environment implements \ArrayAccess
{

    /**
     * 被允许的自定义运行时环境参数
     *
     * @var array
     */
    const ENV_CUSTOM = [
    ];

    /**
     * 默认的环境配置函数数组
     *
     * @var array
     */
    const ENV_DEFAULT = [
        'FRAMEWORK_NAME' => Runtime::FRAMEWORK_NAME,
        'FRAMEWORK_PATH' => Runtime::FRAMEWORK_PATH,
        'FRAMEWORK_VERSION' => Runtime::FRAMEWORK_VERSION,
        'PHP_VERSION' => PHP_VERSION,
        'PHP_VERSION_ID' => PHP_VERSION_ID,
        'PHP_OS' => PHP_OS,
        'OS_PID' => NULL,
        'OS_GID' => NULL,
        'OS_UID' => NULL,
        'OS_SYSTEM_NAME' => NULL,
        'OS_HOSTNAME' => NULL,
        'OS_SYSTME_VERSION_NAME' => NULL,
        'OS_SYSTEM_VERSION_INFO' => NULL,
        'OS_MACHINE_TYPE' => NULL,
        'SCRIRT_DIR' => NULL,
        'RUNTIME_PATH' => NULL,
        'RUNTIME_CONF_PATH' => NULL,
        'RUNTIME_TICK_LINE' => 10,
        'RUNTIME_MEMORY_SIZE' => NULL,
        'RUNTIME_DEBUG_BACKTRACE' => NULL,
        'SCRIPT_FILENAME' => NULL,
        'SCRIPT_FILENAME' => NULL,
        'RUNTIME_MODE' => Runtime::RUNTIME_MODE_WEB,

        'RUNTIME_MODE_CONSOLE' => Runtime::RUNTIME_MODE_CONSOLE,
        'RUNTIME_MODE_WEB' => Runtime::RUNTIME_MODE_WEB,
        'RUNTIME_MODE_RPC' => Runtime::RUNTIME_MODE_RPC,
    ];

    /**
     * 单例
     *
     * @var Environment
     */
    protected static $_instance;

    protected static $_defENV = [];

    /**
     * 环境参数列表
     *
     * @var array
     */
    protected $_envdata = [];

    /**
     * 设置运行时的默认环境参数 仅运行时实例化有效
     *
     * @param array $env
     *        环境参数数组
     * @return array
     */
    public static function setEnv(array $env)
    {
        foreach ($env as $ename => $evar)
        {
            if (!in_array($ename, self::ENV_CUSTOM))
            {
                continue;
            }
            self::$_defENV[$ename] = $evar;
        }
        return self::$_defENV;
    }

    /**
     *
     * @获取单例实例
     *
     * @return Environment
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
     * 获取环境参数
     *
     * @param string $varname
     * @return mixed
     */
    public function offsetGet($name)
    {
        if (!key_exists($name, $this->_envdata))
        {
            return;
        }
        if (NULL === $this->_envdata[$name])
        {
            $this->_envdata[$name] = $this->_lazyGet($name);
        }
        return $this->_envdata[$name];
    }

    /**
     * 设置环境参数 只读 设置报错
     *
     * @param
     *        string
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        return;
    }

    /**
     * 销毁 只读 调用报错
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        return;
    }

    /**
     * 是否有类似这个环境参数
     *
     * @param string $offset
     */
    public function offsetExists($offset)
    {
        return isset($this->_envdata);
    }

    /**
     * 缓存单例初始化系统参数
     * * 魔术方法获取 OS_PID
     * OS_GID
     * OS_UID
     * OS_SYSTEM_NAME
     * OS_HOSTNAME
     * OS_SYSTME_VERSION_NAME
     * OS_SYSTEM_VERSION_INFO
     * OS_MACHINE_TYPE
     * RUNTIME_SCRIRT_DIR
     * RUNTIME_MEMORY_SIZE
     * RUNTIME_DEBUG_BACKTRACE
     * RUNTIME_SCRIPT_FILENAME
     */
    protected function __construct()
    {
        $env = array_merge($_SERVER, $_ENV, self::ENV_DEFAULT, self::$_defENV);
        if ('cli' == php_sapi_name())
        {
            $env['RUNTIME_MODE'] = $env['RUNTIME_MODE_CONSOLE'];
        }
        if ('FRPC_POST' == $_POST['FRPC_METHOD'] || 'FRPC_POST' == $_SERVER['REQUEST_METHOD'])
        {
            $env['RUNTIME_MODE'] = $env['RUNTIME_MODE_RPC'];
        }
        $this->_envdata = $env;
    }


    /**
     * 惰性获取
     *
     * @param string $varname
     *        环境参数名
     * @return mixed
     */
    protected function _lazyGet($varname)
    {
        switch ($varname)
        {
            case 'OS_PID':
                return getmypid();
            case 'OS_GID':
                return getmygid();
            case 'OS_UID':
                return getmyuid();
            case 'OS_SYSTEM_NAME':
                return php_uname('s');
            case 'OS_HOSTNAME':
                return php_uname('n');
            case 'OS_SYSTME_VERSION_NAME':
                return php_uname('r');
            case 'OS_SYSTEM_VERSION_INFO':
                return php_uname('v');
            case 'OS_MACHINE_TYPE':
                return php_uname('m');
            case 'SCRIRT_DIR':
                return dirname(get_included_files()[0]);
            case 'RUNTIME_MEMORY_SIZE':
                return memory_get_usage();
            case 'RUNTIME_DEBUG_BACKTRACE':
                return debug_backtrace();
            case 'SCRIPT_FILENAME':
                return get_included_files()[0];
        }
    }
}

/**
 * 异常注册接口
 *
 * @author king
 */
interface IExceptionHandler
{

    /**
     * 异常发生事件触发
     *
     * @param
     *        \Exception 异常实例
     * @param $exceptions array
     *        异常数组
     * @return void
     */
    public function onException($exception, $exceptions);
}

/**
 * MVC异常处理
 *
 * @package Tiny
 * @since : 2013-3-22上午06:15:37
 * @final : 2017-3-22上午06:15:37
 */
class ExceptionHandler
{

    /**
     * 错误名集合
     *
     * @var array
     *
     */
    const _errorTypes = array(
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
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
    );

    /**
     * 单例
     *
     * @var self
     */
    protected static $_instance;

    /**
     * 需要抛出异常的错误级别数组
     *
     * @var array
     *
     */
    protected $_throwErrorTypes = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR,
        0
    ];

    /**
     * 所有异常情况集合
     *
     * @var array
     *
     */
    protected $_exceptions = [];

    /**
     * 注册的异常处理句柄
     *
     * @var array
     *
     */
    protected $_exceptionHandlers = [];

    /**
     * 获取单例
     *
     * @param
     *        void
     * @return ExceptionHandler
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
     * 初始化异常捕获句柄
     *
     *
     * @param
     *        void
     * @return void
     */
    protected function __construct()
    {
        set_exception_handler([
            $this,
            'onException'
        ]);
        set_error_handler([
            $this,
            'onError'
        ]);
    }

    /**
     * 注册异常处理触发事件
     *
     *
     * @param $handler IExceptionHandler
     *        完成异常处理接口的函数
     * @return void
     */
    public function regExceptionHandler(IExceptionHandler $handler)
    {
        $this->_exceptionHandlers[] = $handler;
    }

    /**
     * 错误触发时调用的函数
     *
     * @param
     *        ……
     * @return void
     */
    public function onError($errno, $errstr, $errfile, $errline)
    {
        if ($errno == E_NOTICE || $errno == 2048)
        {
            return;
        }
        $exception = [
            'level' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'handler' => 'Exception',
            'isThrow' => $this->isThrowError($errno)
        ];
        $this->_exceptions[] = $exception;
        if (!$this->_exceptionHandlers)
        {

            return $this->_throwException($exception);
        }
        foreach ($this->_exceptionHandlers as $handler)
        {
            $handler->onException($exception, $this->_exceptions);
        }
    }

    /**
     * 产生异常时调用的函数
     *
     *
     * @param \Exception $exception
     *        异常对象
     * @return void
     */
    public function onException($e)
    {
        $level = $e->getCode();
        if ($level == E_NOTICE || $level == 2048)
        {
            return;
        }
        $exception = [
            'level' => $level,
            'message' => $e->getMessage(),
            'handler' => get_class($e),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'isThrow' => $this->isThrowError($level)
        ];

        $this->_exceptions[] = $exception;
        if (!$this->_exceptionHandlers)
        {
            return $this->_throwException($exception);
        }

        foreach ($this->_exceptionHandlers as $handler)
        {
            $handler->onException($exception, $this->_exceptions);
        }
    }

    /**
     * 获取所有异常信息数组
     *
     * @param
     *        void
     * @return array
     */
    public function getExceptions()
    {
        return $this->_exceptions;
    }

    /**
     * 获取错误类型名称
     *
     * @param int $level
     *        错误级别
     * @return string
     */
    public function getErrorType($level)
    {
        return isset($this->_errorTypes[$level]) ? $this->_errorTypes[$level] : $this->_errorTypes[0];
    }

    /**
     * 是否是需要抛出异常的错误级别
     *
     * @param int $errno
     *        错误级别
     * @return bool
     */
    public function isThrowError($errno)
    {
        return in_array($errno, $this->_throwErrorTypes);
    }

    /**
     * 默认的抛出异常和错误函数
     *
     * @param $exception string
     *        最新一次异常
     * @return void
     */
    protected function _throwException($exception)
    {
        if (!$exception['isThrow'])
        {
            return;
        }
        foreach ($this->_exceptions as $e)
        {
            var_dump($e);
        }
        exit($exception['level']);
    }
}
?>