<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Environment.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午8:59:16
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午8:59:16 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Runtime;

use function Composer\Autoload\includeFile;

/**
 * 当前运行时(Runtime)的环境和平台参数。此类不能被继承。Readonly
 *
 * @package Tiny.Runtime
 * @since 2013-3-30下午12:27:47
 * @final 2013-11-26下午
 */
class Environment implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * 默认的环境配置函数数组
     *
     * @var array
     */
    const ENV_DEFAULT = [
        'PHP_VERSION' => PHP_VERSION,
        'PHP_VERSION_ID' => PHP_VERSION_ID,
        'PHP_OS' => PHP_OS,
        'PHP_PATH' => null,
        'PID' => null,
        'GID' => null,
        'UID' => null,
        'USER' => null,
        'SYSTEM_NAME' => null,
        'HOSTNAME' => null,
        'SYSTME_VERSION_NAME' => null,
        'SYSTEM_VERSION_INFO' => null,
        'MACHINE_TYPE' => null,
        'RUNTIME_TICK_LINE' => 10,
        'RUNTIME_MEMORY_SIZE' => null,
        'RUNTIME_DEBUG_BACKTRACE' => null,
        'SCRIPT_DIR' => null,
        'SCRIPT_FILENAME' => null,
        'PHP_PATH' => null,
        'RUNTIME_MODE' => 0,
        'RUNTIME_MODE_WEB' => 0,
        'RUNTIME_MODE_CONSOLE' => 1,
        'RUNTIME_MODE_RPC' => 2,
        'TINY_ROOT_DIR' => null,
        'TINY_VAR_DIRNAME' => 'var',
        'TINY_PUBLIC_DIRNAME' => 'public',
        'TINY_VENDOR_DIRNAME' => 'vendor',
    ];
    
    /**
     * 默认环境参数数组
     *
     * @var array
     */
    protected static $defaultENV = [];
    
    /**
     * 环境参数列表
     *
     * @var array
     */
    protected $envdata = [];
    
    /**
     * 设置运行时的默认环境参数 仅运行时实例化有效
     *
     * @param array $env 环境参数数组
     * @return array
     */
    public static function setEnv(array $envs)
    {
        foreach ($envs as $ename => $evar) {
            if (in_array($ename, self::ENV_CUSTOM_LIST)) {
                self::$defaultENV[$ename] = $evar;
            }
        }
    }
    
    /**
     * 初始化系统参数
     */
    public function __construct()
    {
        // 合并$_SERVER
        $env = array_merge($_SERVER, $_ENV, self::ENV_DEFAULT, self::$defaultENV);
        $this->initEnv($env);
        $this->envdata = $env;
    }
    
    /**
     * 
     * @param array $env
     */
    protected function initEnv(& $env)
    {
        // 区别运行时环境
        if ('cli' == php_sapi_name()) {
            $env['RUNTIME_MODE'] = $env['RUNTIME_MODE_CONSOLE'];
        } elseif ('FRPC_POST' == $_POST['FRPC_METHOD'] || 'FRPC_POST' == $_SERVER['REQUEST_METHOD']) {
            $env['RUNTIME_MODE'] = $env['RUNTIME_MODE_RPC'];
        }
        
        // 加载本地环境文件
        $localenv = $this->loadEnvFromLocalFile($env);
    }
    
    /**
     * 加载本地.env文件
     */
    protected function loadEnvFromLocalFile(& $env)
    {
        $localEnv = [];
        
        // 本地.env文件
        $rootDir = defined(TINY_ROOT_PATH) ?  TINY_ROOT_PATH : dirname(dirname($_SERVER['SCRIPT_FILENAME']));
        $envfile = rtrim($rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env.local.php';
        
        if (!((extension_loaded('opcache') && opcache_is_script_cached($envfile)) || is_file($envfile))) {
            
        }
    }
    /**
     *
     * {@inheritdoc}
     * @see \Countable::count()
     */
    public function count()
    {
        return count($this->envdata);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($ename)
    {
        if (!key_exists($ename, $this->envdata)) {
            return;
        }
        
        if (null === $this->envdata[$ename]) {
            $this->envdata[$ename] = $this->lazyGet($ename);
        }
        return $this->envdata[$ename];
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        throw new RuntimeException(sprintf('Object properties are not allowed to be set to a value', Environment::class));
    }
    
    /**
     * 只读
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        throw new RuntimeException(sprintf('Object properties are not allowed to be unset to a value', Environment::class));
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return key_exists($offset, $this->envdata);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::rewind()
     */
    public function rewind()
    {
        return reset($this->envdata);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::current()
     */
    public function current()
    {
        $value = current($this->envdata);
        if (null === $value) {
            $key = key($this->envdata);
            echo $key;
            $value = $this->lazyGet($key);
            if ($value) {
                $this->envdata[$key] = $value;
            }
        }
        return $value;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::next()
     */
    public function next()
    {
        return next($this->envdata);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::key()
     */
    public function key()
    {
        return key($this->envdata);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \Iterator::valid()
     */
    public function valid()
    {
        return key($this->envdata) !== null;
    }
    
    /**
     * 惰性获取
     *
     * @param string $ename 环境参数名
     * @return mixed
     */
    protected function lazyGet($ename)
    {
        switch ($ename) {
            case 'PID':
                return getmypid();
            case 'GID':
                return getmygid();
            case 'UID':
                return getmyuid();
            case 'SYSTEM_NAME':
                return php_uname('s');
            case 'HOSTNAME':
                return php_uname('n');
            case 'SYSTME_VERSION_NAME':
                return php_uname('r');
            case 'SYSTEM_VERSION_INFO':
                return php_uname('v');
            case 'MACHINE_TYPE':
                return php_uname('m');
            case 'SCRIPT_DIR':
                return dirname(get_included_files()[0]);
            case 'SCRIPT_FILENAME':
                return get_included_files()[0];
            case 'RUNTIME_MEMORY_SIZE':
                return memory_get_usage();
            case 'RUNTIME_DEBUG_BACKTRACE':
                return debug_backtrace();
            case 'PHP_PATH':
                return $this->envdata['_'];
            case 'TINY_ROOT_PATH':
                if (defined('TINY_ROOT_PATH')) {
                    return TINY_ROOT_PATH;
                }
                
                $currentDir = dirname($_SERVER['SCRIPT_FILENAME']);
                if (basename($currentDir) == self::$defaultENV['TINY_PUBLIC_DIRNAME']) {
                    $rootDir = dirname($currentDir) . DIRECTORY_SEPARATOR;
                    define('TINY_ROOT_DIR', $rootDir);
                   return $rootDir;
                }
        }
    }
}
?>