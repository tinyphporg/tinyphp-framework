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
     * @formatter:off
     * @var array
     */
    const ENV_DEFAULT = [
        'PHP_VERSION' => PHP_VERSION,
        'PHP_VERSION_ID' => PHP_VERSION_ID,
        'FRAMEWORK_VERSION' => '2.0.0',
        'FRAMEWORK_NAME' => 'tinyphp-framework',
        'PHP_OS' => PHP_OS,
        'PHP_PATH' => null,
        'PID' => null,
        'GID' => null,
        'UID' => null,
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
        'RUNTIME_INDEX_FILE' => null,
        'RUNTIME_CACHE_ID' => null,
        'RUNTIME_CACHE_AUTOLOADER_ID' => 'runtime.autloader',
        'RUNTIME_CACHE_PATH' => null,
        'TINY_ENV_PATH' => null,
        'TINY_ROOT_PATH' => null,
        'TINY_CURRENT_PATH' => null,
        'TINY_BIN_PATH' => null,
        'TINY_CONFIG_PATH' => null,
        'TINY_VAR_PATH' => null,
        'TINY_PUBLIC_PATH' => null,
        'TINY_VENDOR_PATH' => null,
        'TINY_CACHE_PATH' => null,
        'TINY_LOG_PATH' => null,
        'TINY_RESOURCES_PATH' => null,
        'TINY_BIN_DIR' => 'bin',
        'TINY_CONFIG_DIR' => 'conf',
        'TINY_VAR_DIR' => 'var',
        'TINY_PUBLIC_DIR' => 'public',
        'TINY_VENDOR_DIR' => 'vendor',
        'TINY_CACHE_DIR' => 'cache',
        'TINY_LOG_DIR' => 'log',
        'TINY_RESOURCES_DIR' => 'resources',
        'APP_ENV' => 'prod',
        'APP_USER' => null,
        'APP_DEBUG_ENABLED' => false,
        'APP_PROFILE_DIR' => 'profile',
    ];
    
    /**
     * 可自定义的系统默认环境变量
     * 
     * @var array
     */
    const ENV_DEFAULT_CUSTOM = [
        'TINY_BIN_DIR',
        'TINY_CONFIG_DIR',
        'TINY_VAR_DIR',
        'TINY_PUBLIC_DIR',
        'TINY_VENDOR_DIR',
        'APP_ENV',
        'APP_DEBUG_ENABLED',
        'RUNTIME_CACHE_ID',
        'APP_USER',
        'APP_PROFILE_DIR'
    ];
    
    /**
     * 环境参数列表
     *
     * @var array
     */
    protected $envdata = [];
    
    /**
     * 初始化系统参数
     */
    public function __construct()
    {
        // 合并$_SERVER
        $env = array_merge( $_SERVER, $_ENV);
        foreach($env as $key => $val) {
            if (key_exists($key, self::ENV_DEFAULT) && !in_array($key, self::ENV_DEFAULT_CUSTOM)) {
                unset($env[$key]);
            }
        }
        $env = array_merge(self::ENV_DEFAULT, $env);
        $this->initEnv($env);
        $this->envdata = $env;
    }
    
    /**
     *
     * @param array $env
     */
    protected function initEnv(&$env)
    {        
        $env['RUNTIME_INDEX_FILE'] = get_included_files()[0];
        $currentDir = dirname($env['RUNTIME_INDEX_FILE']);
        $env['TINY_CURRENT_PATH'] = $currentDir;
        
        // rootdir;
        if (defined('TINY_ROOT_PATH')) {
            $rootdir = rtrim(TINY_ROOT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $envdir = $rootdir;
        }
        
        // env dir
        if (defined('TINY_PHAR_ID') && defined('TINY_HOME_DIR')) {  // 单文件phar
            $envdir = TINY_HOME_DIR;
        } elseif(!isset($envdir)) {
            if (basename($currentDir) != $env['TINY_PUBLIC_DIR']) {
                throw new \RuntimeException(sprintf('Runtime\Environment class initialization error: [%s] not match Runtime\Environment::TINY_PUBLIC_DIR[%s]', $currentDir, $env['TINY_PUBLIC_DIR']));
            }
            $envdir = dirname($currentDir) . DIRECTORY_SEPARATOR;
        }

        // 环境路径
        $env['TINY_ENV_PATH'] = $envdir;
        $env['TINY_ROOT_PATH'] = $rootdir ?? dirname($currentDir) . DIRECTORY_SEPARATOR;
        $env['TINY_PUBLIC_PATH'] = $envdir . $env['TINY_PUBLIC_DIR'] . DIRECTORY_SEPARATOR;
        $env['TINY_VAR_PATH'] = $envdir . $env['TINY_VAR_DIR'] . DIRECTORY_SEPARATOR;
        $env['TINY_BIN_PATH'] = $envdir . $env['TINY_BIN_DIR'] . DIRECTORY_SEPARATOR;
        $env['TINY_CONF_PATH'] = $envdir . $env['TINY_CONF_DIR'] . DIRECTORY_SEPARATOR;
        $env['TINY_VENDOR_PATH'] = $envdir . $env['TINY_VENDOR_DIR'] . DIRECTORY_SEPARATOR;
        $env['TINY_CACHE_PATH'] = $env['TINY_VAR_PATH'] . $env['TINY_CACHE_DIR'] . DIRECTORY_SEPARATOR;
        $env['TINY_RESOURCES_PATH'] =  $envdir . $env['TINY_RESOURCES_DIR'] . DIRECTORY_SEPARATOR;
        $env['TINY_LOG_PATH'] = $env['TINY_VAR_PATH'] . $env['TINY_LOG_DIR'] . DIRECTORY_SEPARATOR;
        
        // 加载本地环境文件
        $this->initLocalEnv($env['APP_ENV'], $envdir, $env);
        $env['RUNTIME_CACHE_ID'] = md5($env['RUNTIME_INDEX_FILE']) .'.'. $env['APP_ENV'];
        $env['RUNTIME_CACHE_PATH'] = $env['TINY_CACHE_PATH'];
        // RUNTIME_MODE
        if ('cli' == php_sapi_name()) {
            $env['RUNTIME_MODE'] = $env['RUNTIME_MODE_CONSOLE'];
            $this->initConsoleUSER($env);

        } elseif ('FRPC_POST' == $_POST['FRPC_METHOD'] || 'FRPC_POST' == $_SERVER['REQUEST_METHOD']) {
            $env['RUNTIME_MODE'] = $env['RUNTIME_MODE_RPC'];
        }
    }
    
    /**
     * 初始化命令行环境下的进程用户组和用户
     * @param array $env
     */
    protected function initConsoleUSER(&$env)
    {
        if (!$env['APP_USER'] || posix_getegid() !== 0) {
            return;
        }
       
        $userinfo = posix_getpwnam($env['APP_USER']);
        if (!is_array($userinfo)) {
            return;
        }
        $env['GID'] = $userinfo['gid'];
        $env['UID'] = $userinfo['uid'];
        
        posix_setegid($env['GID']);
        posix_seteuid($env['UID']);
    }
        
    /**
     *
     * @param array $env
     * @throws \RuntimeException
     * @return array
     */
    protected function initLocalEnv($defaultEnv, $envdir, &$env)
    {
        $localEnv = $this->readFromLocalFile($envdir, $defaultEnv);
        if (!$localEnv) {
            return [];
        }
        $env = array_merge($env, $localEnv);
    }
    
    /**
     * 读取本地.env文件
     */
    protected function readFromLocalFile($envdir, $defaultEnv)
    {
        // 本地.env文件
        $envfile = $envdir . '.env.local.php';
        if ((extension_loaded('opcache') && opcache_is_script_cached($envfile)) || is_file($envfile)) {
            $localEnv = include ($envfile);
            if (is_array($localEnv)) {
                $localEnv = $this->formatLocalEnvs($localEnv);
                if (!key_exists('APP_ENV', $localEnv)) {
                    $localEnv['APP_ENV'] = $defaultEnv;
                }
                if ($localEnv['APP_ENV'] == 'prod') {
                    return $localEnv;
                }
            }
        }
        
        // 非prod模式下 读取.env文件
        $envsourcefile = $envdir . '.env';
        // .env文件不存在时，以.env.local.php配置为准
        if (!is_file($envsourcefile)) {
            return is_array($localEnv) ? $localEnv : [];
        }
        
        // 读取.ini方式
        $localEnv = parse_ini_file($envsourcefile);
        if (false === $localEnv) {
            throw new \RuntimeException(sprintf('Parsing exception: .env[%s] parsing error', $envsourcefile));
        }
        
        // 基于性能考虑，prod模式下自动生成.env.local.php文件
        if (key_exists('APP_ENV', $localEnv) && $localEnv['APP_ENV'] == 'prod') {
            file_put_contents($envfile, sprintf("<?php\nreturn %s\n?>" , var_export($localEnv, true)), LOCK_EX);
        }
        return $localEnv;
    }
    
    /**
     * 格式化本地ENV变量
     * 
     * @param array $localEnv
     * @throws \RuntimeException
     */
    protected function formatLocalEnvs(array $envs)
    {
        $localEnvs = [];
        foreach ($envs as $key => $val) {
            // ENV变量名必须全部大写，以_下划线分割
            if (!preg_match('/^[A-Z][A-Z0-9]*(_[A-Z0-9]+)*$/', $key)) {
                continue;
            }
            if (key_exists($key, self::ENV_DEFAULT) && !in_array($key, self::ENV_DEFAULT_CUSTOM)) {
                throw new \RuntimeException(sprintf('Invalid environment variable: [%s] is not allowed to be set!', $key));
            }
            $localEnvs[$key] = $val;
        }
        return $localEnvs;
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
        }
    }
}
?>