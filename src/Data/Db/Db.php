<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Db.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-28上午03:40:18
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-28上午03:40:18 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Data\Db;

use Tiny\Data\DataSourceInterface;
use Tiny\Data\Db\Mysql\Mysqli;
use Tiny\Data\Db\Mysql\Pdo;

/**
 * Db数据源
 *
 * @package Tiny.Data.Db
 * @since 2013-11-28上午03:41:37
 * @final 2013-11-28上午03:41:37
 */
class Db implements DataSourceInterface
{
    
    /**
     * 最多保存纪录DB的QUERY数目
     *
     * @var int
     */
    const DB_QUERY_RECORD_MAX = 100;
    
    /**
     * 是否记录query sql
     *
     * @var bool
     */
    protected static $isRecord = false;
    
    /**
     * 执行的数据库语句集合
     *
     * @var array
     */
    protected static $queryRecords = [];
    
    /**
     * Db的适配器映射表
     *
     * @var array
     */
    protected static $adapterMap = [
        'mysqli' => Mysqli::class,
        'mysql_pdo' => Pdo::class
    ];
    
    /**
     * 数据库适配器实例
     *
     * @var DbAdapterInterface
     */
    protected $dbAdapter;
    
    /**
     * 数据库连接的策略数组
     *
     * @var array
     */
    protected $config = [
        'adapter' => 'mysql_pdo',
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => '',
        'dbname' => 'test',
        'charset' => 'utf8',
        'timeout' => 0,
        'is_record' => false,
        'pconnect' => false
    ];
    
    /**
     * 注册数据库适配器
     *
     * @param string $id 适配器ID
     * @param string $adapterClass 适配器类
     */
    public static function regDbAdapter($id, $adapterClass)
    {
        if (self::$adapterMap[$id]) {
            throw new DbException(sprintf('Failed to add db adapter:  adapter id %s already exists', $id));
        }
        self::$adapterMap[$id] = $adapterClass;
    }
    
    /**
     * 添加语句执行信息
     *
     * @param string $sql sql语句
     * @param int $time 执行时间
     * @param string $engineName 数据库引擎
     */
    public static function addQuery($sql, $time, $engineName)
    {
        if (!self::$isRecord) {
            return;
        }
        if (self::DB_QUERY_RECORD_MAX <= count(self::$queryRecords)) {
            array_shift(self::$queryRecords);
        }
        
        self::$queryRecords[] = [
            'sql' => $sql,
            'time' => $time,
            'engine' => $engineName
        ];
    }
    
    /**
     * 获取查询集合
     *
     * @return array
     */
    public static function getQuerys()
    {
        return self::$queryRecords;
    }
    
    /**
     * 通过配置数组初始化实例
     *
     * @param array $config 数据源配置
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        
        // recode querys
        self::$isRecord = (bool)$this->config['is_record'];
        
        // check adapter
        $adapter = $this->config['adapter'];
        if (!$adapter) {
            throw new DbException('Failed to instantiate data source adapter: config.adapter is not set');
        }
        
        if (!key_exists($adapter, self::$adapterMap)) {
            throw new DbException(sprintf('Failed to instantiate data source adapter: db.adapter:%s does not exists!', $adapter));
        }
        $this->config['adapterClass'] = self::$adapterMap[$adapter];
    }
    
    /**
     * 返回连接后的类或者句柄
     *
     * @return mixed
     *
     */
    public function getConnector()
    {
        return $this->getDbAdapter()->getConnector();
    }
    
    /**
     * 关闭或者销毁实例和链接
     */
    public function close()
    {
        $this->getDbAdapter()->close();
    }
    
    /**
     * 执行SQL查询 并返回所有结果集
     *
     * @param string $sql SQL查询语句
     * @param string $param 可变参数数组
     * @return array
     */
    public function fetchAll($sql, ...$param)
    {
        if ($param) {
            $sql = $this->parseBindSql($sql, ...$param);
        }
        return $this->getDbAdapter()->fetchAll($sql);
    }
    
    /**
     * 执行SQL并返回结果集的第一行(一维数组)
     *
     * @param string $sql SQL查询语句
     * @return array
     */
    public function fetch($sql, ...$param)
    {
        if ($param) {
            $sql = $this->parseBindSql($sql, ...$param);
        }
        return $this->getDbAdapter()->fetch($sql);
    }
    
    /**
     * 获取以指定字段名为key的二维数组结果集
     * 注意： 必须有唯一索引或者主键，如果有重复值，则数组会被替代，导致结果集数目不准确。
     *
     * @param string $sql SQL语句
     * @param string $columnName 字段名 为空时默认取第一个字段
     * @param array $param 可变参数数组
     * @return array
     */
    public function fetchAssoc($sql, $columnName = null, ...$param)
    {
        if ($param) {
            $sql = $this->parseBindSql($sql, ...$param);
        }
        $rows = $this->fetchAll($sql);
        if (!$rows) {
            return [];
        }
        if (null == $columnName) {
            $columnName = key(current($rows));
        }
        return array_column($rows, null, $columnName);
    }
    
    /**
     * 返回指定字段名的一列值 组成的一维数组
     *
     * @param string $sql
     * @return array
     */
    public function fetchColumn($sql, $columnName = null, ...$param)
    {
        if ($param) {
            $sql = $this->parseBindSql($sql, ...$param);
        }
        $rows = $this->fetchAll($sql);
        if (!$rows) {
            return [];
        }
        if (null == $columnName) {
            $columnName = key(current($rows));
        }
        return array_column($rows, $columnName);
    }
    
    /**
     * 返回结果集中的第一行指定字段名的值 字段名为空则默认取第一个
     *
     * @param string $sql SQL查询语句
     * @return mixed
     */
    public function fetchCeil($sql, $columnName = null, $index = 0, ...$param)
    {
        if ($param) {
            $sql = $this->parseBindSql($sql, ...$param);
        }
        $row = $this->fetch($sql);
        if (!$row) {
            return '';
        }
        if (null == $columnName) {
            return current($row);
        }
        return $row[$columnName];
    }
    
    /**
     * 获取一个单元
     *
     * @param string $sql SQL语句
     * @param array $param 绑定参数
     * @return string
     */
    public function fetchFirstCeil($sql, ...$param)
    {
        return $this->fetchCeil($sql, null, 0, ...$param);
    }
    
    /**
     * 执行Db涉及写操作的语句
     *
     * @param string $sql SQL查询语句
     * @return bool
     */
    public function exec($sql, ...$param)
    {
        if ($param) {
            $sql = $this->parseBindSql($sql, ...$param);
        }
        return $this->getDbAdapter()->exec($sql) ? true : false;
    }
    
    /**
     * 返回调用当前查询后的结果集中的记录数
     *
     * @return int
     */
    public function rowsCount()
    {
        return $this->getDbAdapter()->rowsCount();
    }
    
    /**
     * 返回当前数据里的所有可用数据库
     *
     * @return array
     */
    public function getDbs()
    {
        return $this->getDbAdapter()->getDbs();
    }
    
    /**
     * 获取该数据库下的所有表
     *
     *
     * @return array
     */
    public function getTables($dbName = '')
    {
        $sql = "SHOW TABLES";
        if (null != $dbName) {
            $sql .= ' FROM ' . $dbName;
        }
        return $this->fetchAll($sql);
    }
    
    /**
     * 返回指定表的所有字段名
     *
     *
     * @param string $table 表名
     * @return array
     */
    public function getTableColumns($tableName)
    {
        return $this->fetchColumn('SHOW COLUMNS FROM ' . $tableName);
    }
    
    /**
     * 魔法调用
     *
     * @param string $method 函数名称
     * @param array $params 参数数组
     * @return
     *
     */
    public function __call($method, $params)
    {
        // @formatter:off
        return call_user_func_array([$this->getDbAdapter(), $method], $params);
        // @formatter:on
    }
    
    /**
     * 解析SQL语句中绑定的参数
     *
     * @param string $sql SQL语句
     * @param ...array $param 可变参数，输入的绑定数组值
     * @return string
     */
    public function parseBindSql($sql, ...$param)
    {
        if (!$param) {
            return $sql;
        }
        return preg_replace_callback("/:([0-9]{1,2})([tfwuis]?)/s", function ($matchs) use ($param) {
            return $this->_parseSqlParam($matchs, $param);
        }, $sql);
    }
    
    /**
     * 解析SQL语句中的绑定变量和占位符
     *
     * @param array $match 匹配到的字符串
     * @param array $param 绑定的参数数组
     * @return string 返回解析后字符串
     */
    protected function _parseSqlParam($match, $param)
    {
        $pkey = $match[1];
        if (!isset($param[$pkey])) {
            return $match[0];
        }
        
        $ptype = $match[2] ?: 's';
        $pval = $param[$match[1]];
        switch ($ptype) {
            case 't':
                return $this->_parseParamTable($pval); // 解析表名
            case 'f':
                return $this->_parseParamField($pval); // 解析查询字段名
            case 'u':
                return $this->_parseParamUpdate($pval); // 解析更新设置
            case 'w':
                return $this->_parseParamWhere($pval); // 解析WHERE条件查询
            case 'i':
                return $this->_parseParamInsert($pval); // 解析插入语句
            default:
                return $pval;
        }
    }
    
    /**
     * 格式化表字段 仅包括需要加反引号情况
     *
     * @param string $pval
     * @return string
     */
    protected function _parseParamTable($pval)
    {
        return sprintf('`%s`', $pval);
    }
    
    /**
     * 格式化字段 仅用于查询字段或者insert字段
     * 检测为单一字段时，会自动添加反引号
     * 数组时，会自动添加反引号并用,链接
     *
     * @param string|array $pval
     * @return string
     */
    protected function _parseParamField($pval)
    {
        $pval = is_array($pval) ? $pval : [
            $pval
        ];
        array_walk($pval, function (&$v) {
            $v = trim($v);
            if (false === strpbrk($v, ' ,.*')) {
                $v = sprintf('`%s`', $v);
            }
        });
        return join(',', $pval);
    }
    
    /**
     * 格式化更新字符串
     *
     * @param array || string $pval
     * @return string
     */
    protected function _parseParamUpdate($pval)
    {
        if (!is_array($pval)) {
            return $pval;
        }
        $pv = [];
        foreach ($pval as $k => $v) {
            if (!is_numeric($v)) {
                $v = addcslashes($v, "'");
            }
            if (false === strpbrk($k, ' ,.')) {
                $k = sprintf('`%s`', $k);
            }
            $pv[] = sprintf("%s='%s'", $k, $v);
        }
        return join(',', $pv);
    }
    
    /**
     * 格式化WHERE条件查询语句
     *
     * @param string || array
     * @return string
     */
    protected function _parseParamWhere($pval)
    {
        if (!is_array($pval)) {
            return $pval;
        }
        return join(' AND ', $pval);
    }
    
    /**
     * 格式化插入语句
     * 一维数组 插入一条
     * 二维数组 插入多条
     *
     * @param string|array $pval 插入数组
     *       
     * @return string
     */
    protected function _parseParamInsert($pval)
    {
        if (!is_array($pval)) {
            return $pval;
        }
        
        if (count($pval, 1) == count($pval)) {
            $keys = array_keys($pval);
            $vals = [];
            foreach ($keys as &$key) {
                $v = $pval[$key];
                if (!is_numeric($v)) {
                    $v = addcslashes($v, "'");
                }
                $vals[] = $v;
                if (false === strpbrk($key, ' ,.')) {
                    $key = sprintf('`%s`', $key);
                }
            }
            return sprintf("(%s) VALUES ('%s')", join(",", $keys), join("','", $vals));
        }
        
        $keys = array_keys(current($pval));
        reset($pval);
        $vstr = [];
        foreach ($pval as $val) {
            $vs = [];
            foreach ($keys as $key) {
                $v = isset($val[$key]) ? $val[$key] : '';
                $vs[] = is_numeric($v) ? $v : addcslashes($v, "'");
            }
            $vstr[] = sprintf("('%s')", join("','", $vs));
        }
        return sprintf("(`%s`) VALUES %s", join("`,`", $keys), join(",", $vstr));
    }
    
    /**
     * 获取数据库适配器实例
     * 
     * @throws DbException
     * @return \Tiny\Data\Db\DbAdapterInterface
     */
    protected function getDbAdapter()
    {
        if (!$this->dbAdapter) {
            $config = $this->config;
            $adapterClass = $config['adapterClass'];
            unset($config['adapter'], $config['adapterClass']);
            
            // create adapter
            $this->dbAdapter = new $adapterClass($config);
            if (!$this->dbAdapter instanceof DbAdapterInterface) {
                throw new DbException(sprintf('Failed to get database adapter:%s is not instance of %s', $adapterClass, DbAdapterInterface::class));
            }
        }
        return $this->dbAdapter;
    }
}
?>