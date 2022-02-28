<?php
/**
 *
 * @copyright (C), 2011-, King.
 * @Name: Config.php
 * @Author: King
 * @Version: Beta 1.0
 * @Date: 2013-4-5下午12:29:59
 * @Description:
 * @Class List:
 *        1.
 * @Function List:
 *           1.
 * @History: <author> <time> <version > <desc>
 *           King 2013-4-5下午12:29:59 Beta 1.0 第一次建立该文件
 *
 */
namespace Tiny\Config;

use Tiny\Cache\Cache;
use Tiny\Config\Parser\ParserInterface;
use Tiny\Config\Parser\IniParser;
use Tiny\Config\Parser\JSONParser;
use Tiny\Config\Parser\YamlParser;

/**
 * 配置类
 *
 * @package Tiny.Config
 * @since ：Mon Oct 31 23 54 26 CST 2011
 * @final :Mon Oct 31 23 54 26 CST 2011
 *        2018-02-12 修改与优化类 路径为文件夹下，可以自动寻找下级目录的配置文件
 */
class Configuration implements \ArrayAccess, ParserInterface
{
    
    /**
     * 注册的配置解析器工具类数组
     *
     * @var array
     */
    protected static $parserMap = [
        'ini' => IniParser::class,
        'json' => JSONParser::class,
        'yml' => YamlParser::class
    ];
    
    /**
     * 缓存实例
     *
     * @var Cache
     */
    protected $cache;
    
    /**
     * 缓存的文件路径对应的数据，避免多次加载
     *
     * @var array
     */
    protected static $filedata = [];
    
    /**
     * 解析数组
     * 
     * @var array
     */
    protected $parsers = [];
    
    /**
     * 该配置初始化路径是否为文件
     *
     * @var string
     */
    protected $isFile = false;
    
    /**
     * 该配置文件/文件夹的路径
     *
     * @var string
     */
    protected $path;
    
    /**
     * 配置文件路径数组
     *
     * @var array
     */
    protected $paths = [];
    
    /**
     * 在配置文件夹中读取的变量数组
     *
     * @var array
     */
    protected $data = null;
    
    /**
     * 注册配置解析器映射
     *
     * @param string $ext 文件扩展名
     * @param string $parser 解析器类名
     */
    public static function regConfigParser(string $ext, $parser)
    {
        self::$parserMap[$ext] = $parser;
    }
    
    /**
     * 初始化配置文件路径
     *
     * @param string $cpath 配置文件或者文件夹路径
     * @return void
     */
    public function __construct($cpath)
    {
        if (is_array($cpath)) {
            $this->paths = $cpath;
        } else {
            $this->paths[] = $cpath;
        }
        
        foreach ($this->paths as $path) {
            if (!file_exists($path)) {
              throw new ConfigException(sprintf('Class %s instantiation failed: the path %s does not exists', self::class), E_ERROR);
            }
        }
        $this->initParser();
    }
    
    /**
     * 解析PHP文件
     *
     * @param string $fpath 文件或文件夹路径
     * @see \Tiny\Config\Parser\ParserInterface::parse()
     * @return mixed 返回PHP配置数据，会遵循先获取返回值，如果返回值为空或者false 再去搜寻与文件名同名的变量值
     */
    public function parse($fpath)
    {
        $rval = include($fpath);
        if (!(false === $rval || $rval === 1)) {
            return $rval;
        }
        
        $fname = basename($fpath, '.php');
        $ival = ${$fname};
        return  (isset($ival)) ? $ival : null;
    }
    
    /**
     * 设置配置实例的初始化数据
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        if (!$data) {
            return;
        }
        $this->data = $data;
    }
    
    /**
     * 获取配置 ,例如 setting.a.b 配置节点以.分隔
     *
     * @param string $node 节点名
     * @return mixed
     */
    public function get($node = null)
    {
        $nodes = $this->parseNode($node);
        if (null === $nodes) {
            return $this->data;
        }
        $data = $this->data;
        foreach ($nodes as $n) {
            if (!is_array($data)) {
                return null;
            }
            $data = &$data[$n];
        }
        return $data;
    }
    
    /**
     * 设置
     *
     * @param string $node 节点设置
     * @param $val string 值
     * @return bool
     */
    public function set($node, $val)
    {
        $nodes = $this->parseNode($node);
        $ret = &$this->data;
        foreach ($nodes as $n) {
            $ret = &$ret[$n];
        }
        $ret = $val;
    }
    
    /**
     * 移除参数
     *
     * @param string $node 节点名
     * @return void
     */
    public function remove($node)
    {
        $this->set($node, null);
    }
    
    /**
     * 是否存在某个配置节点
     *
     * @param string $node 节点
     * @return bool
     */
    public function exists($node)
    {
        return (bool)$this->get($node);
    }
    
    /**
     * ArrayAccess接口必须函数，是否存在
     *
     * @param string $node 解析参数
     * @return bool
     */
    public function offsetExists($node)
    {
        return $this->exists($node);
    }
    
    /**
     * ArrayAccess接口必须函数，设置
     *
     * @param string $node 节点名
     * @param mixed $value 值
     *        解析参数
     * @return void
     */
    public function offsetSet($node, $value)
    {
        return $this->set($node, $value);
    }
    
    /**
     * ArrayAccess接口必须函数，获取
     *
     * @param string $node 节点名
     * @return mixed
     */
    public function offsetGet($node)
    {
        return $this->get($node);
    }
    
    /**
     * ArrayAccess接口必须函数 ,移除
     *
     * @param string $node 节点名
     * @return bool
     */
    public function offsetUnset($node)
    {
        return $this->remove($node);
    }
    
    /**
     * 解析节点 .分隔
     *
     * @param string $node 节点名
     * @return array
     */
    protected function parseNode($node)
    {
        if (null === $this->data) {
            $this->initDataByPath();
        }
        $nodes = null == $node ? null : explode('.', $node);
        return $nodes;
    }
    
    /**
     * 初始化解析器 并默认注入PHP的解析器
     */
    protected function initParser()
    {
        foreach (self::$parserMap as $ext => $cn) {
            $this->parsers[$ext] = [
                'className' => $cn,
                'instance' => null
            ];
        }
        
        if (!key_exists('php', $this->parsers)) {
            $this->parsers['php'] = [
                'className' => __CLASS__,
                'instance' => $this
            ];
        }
    }
    
    /**
     * 初始化并加载配置数据
     */
    protected function initDataByPath()
    {
        $this->_data = [];
        $this->parseAllDataFromPaths($this->paths, $this->data, true);
    }
    
    /**
     * 一次性获取所有数据 从文件夹
     *
     * @param string $path
     * @param array $d
     * @param bool $isHideNode 是否隐藏节点
     */
    protected function parseAllDataFromPaths($path, &$d, $isHideNode = false)
    {
        $paths = $this->parsePaths($path);
        foreach ($paths as $node => $pathinfo) {
            if ($pathinfo[0]) {
                $data = $this->loadDataFromFile($pathinfo[0][0], $pathinfo[0][1]);
                if ($data) {
                    if ($isHideNode) {
                        $d = (is_array($d) && is_array($data)) ? array_merge($d, $data) : $data;
                    } else {
                        $d[$node] = (is_array($d[$node]) && is_array($data)) ? array_merge($d[$node], $data) : $data;
                    }
                }
            }
            if ($pathinfo[1]) {
                if ($isHideNode) {
                    $this->parseAllDataFromPaths($pathinfo[1], $d);
                } else {
                    $this->parseAllDataFromPaths($pathinfo[1], $d[$node]);
                }
            }
        }
    }
    
    /**
     * 解析配置路径
     *
     * @param array|string $path 配置路径
     * @return array|string|string[]|mixed[]
     */
    protected function parsePaths($path)
    {
        $paths = [];
        $files = is_array($path) ? $path : scandir($path);
        $parentPath = is_array($path) ? '' : $path;
        foreach ($files as $fname) {
            if ($fname == '.' || $fname == '..') {
                continue;
            }
            $fpath = $parentPath . $fname;
            $pathinfo = pathinfo($fpath);
            $node = $pathinfo['filename'];
            if (is_file($fpath) && key_exists($pathinfo['extension'], $this->parsers)) {
                
                $paths[$node][0] = [
                    $fpath,
                    $pathinfo['extension']
                ];
            }
            if (is_dir($fpath)) {
                
                $paths[$node][1] = $fpath . '/';
            }
        }
        return $paths;
    }
    
    /**
     * 从文件加载配置数据
     *
     * @param string $fpath 配置文件路径
     * @param string $extname 文件扩展名
     * @return mixed
     */
    protected function loadDataFromFile($fpath, $extname)
    {
        $parser = $this->getParserByExt($extname);
        if (!$parser) {
            return false;
        }
        if (!isset(self::$filedata[$fpath])) {
            self::$filedata[$fpath] = $parser->parse($fpath);
        }
        return self::$filedata[$fpath];
    }
    
    /**
     * 根据文件扩展名获取解析器
     * 
     * @param string $extname 文件扩展名
     * @throws ConfigException
     * @return boolean|\Tiny\Config\Parser\ParserInterface
     */
    protected function getParserByExt($extname)
    {
        $parserData = $this->parsers[$extname];
        if (empty($parserData)) {
            return false;
        }
        
        if (!$parserData['instance']) {
            $className = $parserData['className'];
            $instance = new $className();
            if (!$instance instanceof ParserInterface) {
                throw new ConfigException(sprintf("Class %s is not an instance of %s!", self::class, ParserInterface::class));
            }
            $parserData['instance'] = $instance;
        }
        return $parserData['instance'];
    }
}
?>