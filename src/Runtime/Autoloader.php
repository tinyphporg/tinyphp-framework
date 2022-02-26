<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Autoloader.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午9:10:39
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午9:10:39 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Runtime;

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
     * 类的命名空间路径映射表
     *
     * @var array
     */
    protected $namspacePathMap = [];
    
    /**
     * 类的路径映射表
     *
     * @var array
     */
    protected $classPathMap = [];
    
    /**
     * 构造函数，主动自动加载类
     *
     * @param void
     * @return void
     */
    public function __construct()
    {
        $this->registerAutoloadHandler();
    }
    
    /**
     * 添加命名空间到路径映射表
     *
     * @param string $namespace 命名空间
     *        $namespace = * 即添加全局命名空间的加载路径
     * @param string $path 单个加载路径
     *        array $path 多个加载路径
     */
    public function addToNamespacePathMap(string $namespace, $path)
    {
        if ('*' === $namespace) {
            $path = rtrim($path, DIRECTORY_SEPARATOR);
            return set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        }
        
        if (!key_exists($namespace, $this->namspacePathMap)) {
            $this->namspacePathMap[$namespace] = [];
        }
        
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->add($namespace, $p);
            }
            return;
        }
        
        if (in_array($path, $this->namspacePathMap[$namespace])) {
            return;
        }
        $this->namspacePathMap[$namespace][] = $path;
    }
    
    /**
     * 获取命名空间的路径映射表
     *
     * @return array
     */
    public function getNamepsacePathMap()
    {
        return $this->namspacePathMap;
    }
    
    /**
     * 添加类到路径映射
     *
     * @param mixed $className
     * @param string $path
     */
    public function addToClassPathMap($className, string $path = null)
    {
        if (is_array($className)) {
            foreach ($className as $cname => $p) {
                $this->addPath($cname, $p);
            }
            return;
        }
        if ($path) {
            $this->classPathMap[$className] = $path;
        }
    }
    
    /**
     * 加载类
     *
     * @param string $className 类名
     */
    public function loadClass($className)
    {
        if (key_exists($className, $this->classPathMap)) {
            $classfile = $this->classPathMap[$className];
            include_once $classfile;
            return;
        }
        
        if (false === strpos($className, "\\")) {
            $classfile = $className . '.php';
            if ($this->classFileExists($classfile)) {
                include_once ($classfile);
            }
            return;
        }
        
        $namespaceClassMap = [];
        $classNodes = explode("\\", $className);
        for ($i = count($classNodes); $i >= 1; $i--) {
            $namespaceClassMap[] = [
                join("\\", array_slice($classNodes, 0, $i)),
                join('/', array_slice($classNodes, $i))
            ];
        }
        
        foreach ($namespaceClassMap as $node) {
            list($namespace, $pathSuffix) = $node;
            if (!key_exists($namespace, $this->namspacePathMap)) {
                continue;
            }
            
            if ($this->loadClassFromPath($namespace, $pathSuffix, $className)) {
                break;
            }
        }
    }
    
    /**
     * 寻找和加载类路径
     *
     * @param string $namespace 寻找的命名空间
     * @param string $pathSuffix 路径尾缀
     * @param string $cname 类名
     * @return bool false加载失败 || true成功
     */
    protected function loadClassFromPath($namespace, $pathSuffix, $classname)
    {
        $paths = $this->namspacePathMap[$namespace];
        
        foreach ($paths as $ipath) {
            $classfile = $ipath . $pathSuffix . '.php';
            if ($this->classFileExists($classfile)) {
                include_once ($classfile);
                return true;
            }
        }
    }
    
    /**
     * 检测classfile是否存在
     *
     * @param string $file 文件路径
     * @return boolean
     */
    protected function classFileExists($file)
    {
        return (extension_loaded('opcache') && opcache_is_script_cached($file)) || is_file($file);
    }
    
    /**
     * 注册自动加载句柄
     *
     * @return boolean
     */
    protected function registerAutoloadHandler()
    {
        $autoloadFunctions = spl_autoload_functions();
        if (!$autoloadFunctions) {
            // @formatter:off
            return spl_autoload_register([$this, 'loadClass']);
        }
        
        // 多个autoloader情况下，tinyphp首先加载
        foreach ($autoloadFunctions as $afunc) {
            spl_autoload_unregister($afunc);
        }
        
        array_unshift($autoloadFunctions, [$this, 'loadClass']);
        
        // @formatter:on
        foreach ($autoloadFunctions as $afunc) {
            spl_autoload_register($afunc);
        }
    }
}
?>