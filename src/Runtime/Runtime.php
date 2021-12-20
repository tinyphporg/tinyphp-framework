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
 *          King 2020年02月19日下午15:44:00 stable 1.0 审定稳定版本
 *
 */
namespace Tiny\Runtime;

use Tiny\MVC\ApplicationBase;

/* 定义框架所在路径 */
define('TINY_FRAMEWORK_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

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
    const FRAMEWORK_VERSION = '1.0.0 stable';

    /**
     * 框架所在目录
     *
     * @var string
     */
    const FRAMEWORK_PATH = TINY_FRAMEWORK_PATH;
    
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
    protected static $instance;

    /**
     * app策略集合
     *
     * @var array 运行时模式对应创建的application具体对象
     *      WEB模式 | CONSOLE模式 | RPC模式
     */
    protected static $appMap = [
        Environment::RUNTIME_MODE_CONSOLE => '\Tiny\MVC\ConsoleApplication',
        Environment::RUNTIME_MODE_WEB => '\Tiny\MVC\WebApplication',
        //Environment::RUNTIME_MODE_RPC => '\Tiny\MVC\RPCApplication'
    ];

    /**
     * 运行时创建的应用程序实例
     *
     * @var ApplicationBase
     */
    protected $app;

    /**
     * 运行时创建的自动加载对象实例
     *
     * @var Autoloader
     */
    protected $autoloader;

    /**
     * 运行时创建的异常处理实例
     *
     * @var ExceptionHandler
     */
    protected $exceptionHandler;
    
    /**
     * 运行时缓存实例集合
     * @var array
     */
    protected $runtimeCaches = [];

    /**
     * 注册或者替换已有的Application
     *
     * @param int $mode
     * @param string $className
     * @return bool TRUE success || TRUE falid
     */
    public static function regApplication($mode, $className): bool
    {
        if (!$className instanceof ApplicationBase)
        {
            return false;
        }
        if (!key_exists($mode, self::$appMap))
        {
            return false;
        }
        self::$appMap[$mode] = $className;
        return true;
    }

    /**
     * 设置当前运行时的应用实例
     * 
     * @param $app ApplicationBase
     */
    public function setApplication(ApplicationBase $app)
    {
        $this->app = $app;
    }
    
    /**
     * 获取当前运行时的应用实例
     * @return \Tiny\MVC\ApplicationBase
     */
    public function getApplication()
    {
        return $this->app;
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
        if (!$this->app)
        {
            $className = self::$appMap[$this->env['RUNTIME_MODE']];
            $this->app = new $className($this, $appPath, $profile);
        }
        return $this->app;
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
        return $this->autoloader->add($path, $namespace);
    }

    /**
     * 获取所加载的库和路径
     * @return array
     */
    public function getImports()
    {
        return $this->autoloader->getImports();
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
     * 创建一个运行时缓存
     * @param string $cacheId
     */
    public function createRuntimeCache($cacheId)
    {
        if (!$this->env['RUNTIME_CACHE_ENABLED'])
        {
            return NULL;
        }
        if (!$this->_runtimeCaches[$cacheId])
        {
           $this->_runtimeCaches[$cacheId] = new RuntimeCacheItem($cacheId);
        }
        return $this->_runtimeCaches[$cacheId];
    }
    
    /**
     * 获取加载器的运行时缓存实例
     * 
     * @return RuntimeCacheItem|FALSE
     */
    public function getAutoloaderCache()
    {
        return $this->createRuntimeCache($this->env['RUNTIME_CACHE_ID_AUTOLOADER']);
    }
    
    /**
     * 获取当前应用实例的运行时缓存实例
     * @return boolean
     */
    public function getApplicationCache()
    {
        return $this->createRuntimeCache($this->env['RUNTIME_CACHE_ID_APPLICATION']);
    }
    
    /**
     *  是否开启运行时缓存
     *  
     * @return \Tiny\Runtime\Environment
     */
    public function isRuntimeCached()
    {
        return $this->env['RUNTIME_CACHE_ENABLED'];       
    }
    
    /**
     * 构建基本运行环境所需的各种类
     *
     * @return void
     */
    protected function __construct()
    {
        $this->env = Environment::getInstance();
        $this->_autoloader = Autoloader::getInstance();
        $acache = $this->getAutoloaderCache();
        if($acache)
        {
            $this->_autoloader->setRuntimeCache($acache);
        }
        $this->_autoloader->add(self::FRAMEWORK_PATH, 'Tiny');
        $this->_exceptionHandler = ExceptionHandler::getInstance();
    }
    
}

/**
* 运行时共享内存缓存
* 
* 
* 设置缓存时间 Tiny::setENV[RUNTIME_CACHE_TTL] = INT
* 是否开启运行时缓存 Tiny::setEnv[RUNTIME_CACHE_ENABLE] = TRUE|FALSE
* 缓存内存大小设置 Tiny::setEnv[RUNTIME_CACHE_MEMORY] = TRUE|FALSE
*  注意：当前版本需要shmop扩展支持，单独实例化使用时不支持>10MB存储
* @package Tiny.Runtime
* @since 2021年8月29日 下午12:28:43
* @final 2021年8月29日下午12:28:43
*/
class RuntimeCacheItem
{
    /**
     * 缓存句柄
     * 
     * @var RuntimeCacheHandler
     */
    protected $_handler;
    
    /**
     * 唯一缓存ID
     * 
     * @var string
     */
    protected $_id;
    
    /**
     * 创建缓存唯一句柄
     * 
     * @param string $id
     */
    public function __construct($id)
    {
        $this->_id = (string)$id;
        $this->_handler = RuntimeCachePool::getInstance();
    }
    
    /**
     *  获取缓存内容
     *  
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function get($key)
    {
        return $this->_handler->get($this->_id, $key);
    }
    
    /**
     * 设置缓存内容
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
       $this->_handler->set($this->_id, $key, $value);
    }
}

/**
 * 运行时缓存
 * @author macbookpro
 *
 */
class RuntimeCachePool
{   
    
    /**
     * 缓存数据头部字节长度
     * @var integer
     */
    const HEADER_LENGTH = 20;
    
    /**
     * 缓存数据时间戳长度 10
     * @var integer
     */
    const HEADER_TIMESTAMP_LENGTH = 10;
    
    /**
     * 单一实例
     * @var \Tiny\Runtime\RuntimeCache
     */
    protected static $_instance;
    
    /**
     * 共享内存的缓存ID
     * @var string
     */
    protected $_memoryId = FALSE;
  
    /**
     * 缓存过期时间
     * @var integer
     */
    protected $_ttl = 60;
    
    /**
     * 共享内存
     * @var int
     */
    protected $_memorySize = 1048576;
    
    /**
     * 缓存数据
     * @var boolean
     */
    protected $_data = FALSE;
    
    /**
     * 是否更新标识
     * 
     * @var boolean
     */
    protected $_isUpdated = FALSE;
    
    /**
     * 获取单一实例
     * @return \Tiny\Runtime\RuntimeCache
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
     * 构造函数
     */
    protected function __construct()
    {
        if (!extension_loaded('shmop'))
        {
            throw new RuntimeException('开启运行时内存需要shmop共享内存扩展支持');  
        }
        $env = Environment::getInstance();
        $this->_memoryId = $env['RUNTIME_CACHE_ID'];
        $this->_memorySize = $env['RUNTIME_CACHE_MEMORY'];
        $this->ttl = $env['RUNTIME_CACHE_TTL'];
    }
    
    /**
     * 获取缓存数据
     * @param string $id 缓存数据节点ID
     * @param string $key 缓存节点的key
     * @return boolean
     */
    public function get($id, $key)
    {
        if (FALSE  === $this->_data)
        {
            $this->_data = $this->_loadData();
        }
    
        if (!isset($this->_data[$id]) || !is_array($this->_data[$id]))
        {
            $this->_isUpdated = TRUE;
           $this->_data[$id]  = []; 
        }
        return $this->_data[$id][$key];
    }
    
    /**
     * 设置缓存数据节点的缓存kv对
     * @param string $id 缓存数据节点ID
     * @param string $key 缓存数据键
     * @param mixed $value 值
     */
    public function set($id, $key, $value)
    {
        if (FALSE  === $this->_data)
        {
            $this->_data = $this->_loadData();
        }
        if (!isset($this->_data[$id]) || !is_array($this->_data[$id]))
        {
            $this->_data[$id]  = [];
        }
        if (!isset($this->_data[$id][$key]) || $this->_data[$id][$key] !== $value)
        {
            $this->_isUpdated = TRUE;
            $this->_data[$id][$key] = $value;
        }
    }
    
    /**
     * 检测更新 并保存到共享内存中
     */
    public function __destruct()
    {
        if (!$this->_isUpdated)
        {
            return;
        }
        $this->_wrtieData();
    }
    
    /**
     * 从共享内存缓存中加载数据
     * 
     * @return array|mixed
     */
    protected function _loadData()
    {

        @$shmId = shmop_open($this->_memoryId, 'c', 0644, $this->_memorySize);
        if(!$shmId)
        {
            return [];
        }
        $timestamp = shmop_read($shmId, 0, self::HEADER_TIMESTAMP_LENGTH);
        if (time() > $timestamp)
        {
            shmop_close($shmId);
            return [];
        }
        $dataLength = (int)shmop_read($shmId, self::HEADER_TIMESTAMP_LENGTH, self::HEADER_LENGTH);
        $cdata = (string)shmop_read($shmId, self::HEADER_LENGTH, $dataLength);
        shmop_close($shmId);
        $data = unserialize($cdata);
        return $data ?:[];
    }
    
    /**
     * 写入共享缓存数据
     * 
     * @return void
     */
    protected function _wrtieData()
    {
        $data = serialize($this->_data);
        $timestamp = time() + $this->_ttl;
        $dataLength = str_pad(strlen($data), 10, 0, STR_PAD_LEFT);
        $cdata =  (string)$timestamp . $dataLength . $data;
        if (self::HEADER_LENGTH + $dataLength >= $this->_memorySize)
        {
            return FALSE;
        }
        $shmId = shmop_open($this->_memoryId, 'c', 0644, $this->_memorySize);
        if(!$shmId)
        {
            return FALSE;
        }
        
        $ret  = shmop_write($shmId, $cdata, 0);
        shmop_close($shmId);
        return $ret;
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
     * 运行时缓存实例
     * 
     * @var RuntimeCache
     */
    protected $_runtimeCache = FALSE;
    
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

    /**
     * 获取加载的库路径
     * 
     * @return array
     */
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
        $ipath = $this->_getPathFromRuntimeCache($cname);
        if ($ipath)
        {
            return include_once($ipath);
        }
        if (FALSE === strpos($cname, "\\"))
        {
            $ipath =  $cname . '.php';
            if (is_file($ipath))
            {
                $this->_saveToRuntimeCache($cname, $ipath);
                include_once($ipath);
            }
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
     * 设置加载器的运行时缓存实例 初始化时自动调用
     * @param RuntimeCacheItem $runtimecache
     */
    public function setRuntimeCache(RuntimeCacheItem $runtimecache)
    {
        $this->_runtimeCache = $runtimecache;
    }
    
    /**
     * 保存class对应的路径到runtimecache实例中
     * @param string $cname
     * @param string $fpath
     * @return boolean
     */
    protected function _saveToRuntimeCache($cname, $fpath)
    {
        if (!$this->_runtimeCache)
        {
            return FALSE;
        }
        return $this->_runtimeCache->set($cname, $fpath);
    }
    
    /**
     * 从runtime中获取类名对应的fpath
     * @param string $cname
     * @return boolean|string
     */
    protected function _getPathFromRuntimeCache($cname)
    {
        if (!$this->_runtimeCache)
        {
            return FALSE;
        }
        return $this->_runtimeCache->get($cname);
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
            $this->_saveToRuntimeCache($cname, $ipath);
            include_once($ipath);
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
        'RUNTIME_CACHE_ENABLED',
        'RUNTIME_CACHE_TTL',
        'RUNTIME_CACHE_MEMORY_MIN',
        'RUNTIME_CACHE_MEMORY_MAX',
        'RUNTIME_CACHE_MEMORY',
        'RUNTIME_DIR',
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
         'RUNTIME_DIR' => null,
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
        'RUNTIME_CACHE_ENABLED' => TRUE,
        'RUNTIME_CACHE_TTL' => 60,
        'RUNTIME_CACHE_MEMORY_MIN' => 1048576,
        'RUNTIME_CACHE_MEMORY_MAX' => 104857600,
        'RUNTIME_CACHE_MEMORY' => 10485760,
        'RUNTIME_CACHE_ID' => NULL,
        'RUNTIME_CACHE_ID_AUTOLOADER' => 'autoloader',
        'RUNTIME_CACHE_ID_APPLICATION' => 'application',
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

    /**
     * 默认环境参数数组
     * 
     * @var array
     */
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
     * 是否为命令行运行环境 
     * 
     * @return boolean
     */
    public function isConsole()
    {
        return $this->_envdata['RUNTIME_MODE'] == $this->_envdata['RUNTIME_MODE_CONSOLE'];
    }

    /**
     * 是否为WEB运行环境
     *
     * @return boolean
     */
    public function isWeb()
    {
        return $this->_envdata['RUNTIME_MODE'] == $this->_envdata['RUNTIME_MODE_WEB'];
    }
    
    /**
     * 是否为RPC运行环境
     *
     * @return boolean
     */
    public function isRpc()
    {
        return $this->_envdata['RUNTIME_MODE'] == $this->_envdata['RUNTIME_MODE_RPC'];
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
        if (!$env['RUNTIME_DIR'] || !is_dir($env['RUNTIME_DIR']))
        {
            
        }
        //cli 或者没有shmop共享内存模块下，默认不进行运行时缓存
        if ($env['RUNTIME_MODE'] == $env['RUNTIME_MODE_CONSOLE'] || !extension_loaded('shmop'))
        {
            $env['RUNTIME_CACHE_ENABLED'] = FALSE;
        }
        
        //缓存内存设置
        if ($env['RUNTIME_CACHE_ENABLED'])
        {
            $cacheMemory = (int)$env['RUNTIME_CACHE_MEMORY'];
            if ($cacheMemory < $env['RUNTIME_CACHE_MEMORY_MIN'])
            {
                $cacheMemory = $env['RUNTIME_CACHE_MEMORY_MIN'];
            }
            if ($cacheMemory > $env['RUNTIME_CACHE_MEMORY_MAX'])
            {
                $cacheMemory = $env['RUNTIME_CACHE_MEMORY_MAX'];
            }
            $env['RUNTIME_CACHE_MEMORY'] = $cacheMemory;
            $env['RUNTIME_CACHE_ID'] = ftok(get_included_files()[0], 0);
        }
        //注入环境变量
        $this->_envdata = $env;
        $_ENV = $env;
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
 * @package Tiny.Runtime
 * @since : 2013-3-22上午06:15:37
 * @final : 2017-3-22上午06:15:37
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
 * 页面无法找到错误定义
 * 
 * @var int
 */
define('E_NOFOUND', 99);

/**
 * MVC异常处理
 *
 * @package Tiny.Runtime
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
    const EXCEPTION_TYPES = array(
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
        E_NOFOUND,
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
        set_exception_handler([$this, 'onException']);
        set_error_handler([$this, 'onError']);
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
            'type' => $this->getErrorType($errno),
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
            'type' => $this->getErrorType($errno),
            'message' => $e->getMessage(),
            'handler' => get_class($e),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'traceString' => $e->getTraceAsString(),
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
        return self::EXCEPTION_TYPES[$level] ?: self::EXCEPTION_TYPES[0];
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