<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Pdo.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-28上午06:55:47
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-11-28上午06:55:47 1.0 第一次建立该文件
 *          King 2020年03月5日15:48:00 stable 1.0 审定稳定版本
 */
namespace Tiny\Data\Db\Mysql;

use Tiny\Data\Db\Db;
use Tiny\Data\Db\DbAdapterInterface;

/**
 * mysqld的PDO构造方式
 *
 * @package Tiny.Data.Db.Mysql
 * @since 2013-11-28上午06:56:26
 * @final 2013-11-28上午06:56:26
 *        King 2020年03月5日15:48:00 stable 1.0 审定稳定版本
 */
class Pdo implements DbAdapterInterface
{
    
    /**
     * 最大重连次数
     *
     * @var int
     */
    const RELINK_MAX = 3;
    
    /**
     * 重连的错误列表
     *
     * @var array
     */
    const RELINK_ERRNO_LIST = [
        2006,
        2013
    ];
    
    /**
     * 配置选型数组
     *
     * @var array
     */
    protected $options = [
        \PDO::ATTR_CASE => \PDO::CASE_LOWER,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING
    ];
    
    /**
     * 自动重连计数器
     *
     * @var int
     */
    protected $relinkCounter = 0;
    
    /**
     * 配置数组
     *
     * @var array
     */
    protected $config;
    
    /**
     * pdo连接
     *
     * @var \PDO
     */
    protected $connector;
    
    /**
     * 最后一次SQL执行返回的PDOStatement
     *
     * @var \PDOStatement
     */
    protected $lastStatement;
    
    /**
     * 统一的构造函数
     *
     * @param array $policy 默认为空函数
     * @return
     *
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    /**
     * 查询事件
     *
     * @param string $sql 查询内容
     * @param float $interval 用时时长
     * @return void
     */
    public function onQuery($sql, $interval)
    {
        return Db::addQuery($sql, $interval, __CLASS__);
    }
    
    /**
     * 错误事件
     *
     * @param string $errmsg 错误信息
     * @return void
     */
    public function onError($errmsg)
    {
        $info = $this->getConnector()->errorInfo();
        throw new MysqlException(sprintf("%s PDO ErrorNO:%d,%s", $errmsg, $info[0], $info[2]));
    }
    
    /**
     * 获取最近一条错误的内容
     *
     * @param void
     * @return string
     */
    public function getErrorMSg()
    {
        return $this->getConnector()->errorInfo()[2];
    }
    
    /**
     * 获取最近一条错误的标示
     *
     * @param void
     * @return int
     *
     */
    public function getErrorNo()
    {
        return $this->getConnector()->errorCode()[1];
    }
    
    /**
     * 获取连接
     *
     * @return \PDO
     */
    public function getConnector()
    {
        if ($this->connector) {
            return $this->connector;
        }
        
        $config = $this->config;
        $options = array_merge($this->options, (array)$config['options']);
        $options[\PDO::ATTR_EMULATE_PREPARES] = true;
        if (isset($config['timeout'])) {
            $options[\PDO::ATTR_TIMEOUT] = (int)$config['timeout'];
        }
        if ($config['pconnect']) {
            $options[\PDO::ATTR_PERSISTENT] = true;
        }
        try {
            
            // 连接计时开始
            $interval = microtime(true);
            $dns = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'],
                $config['dbname'], $config['charset']);
            $this->connector = new \PDO($dns, $config['user'], $config['password'], $options);
            
            // 连接计时
            $this->onQuery(sprintf('db connection %s@%s:%s ...', $config['user'], $config['host'], $config['port']),
                microtime(true) - $interval);
        } catch (\PDOException $e) {
            throw new MysqlException(sprintf("Db connection failed: " . $e->getMessage()));
        }
        return $this->connector;
    }
    
    /**
     * 重载方法：执行 SQL
     *
     * @param string $sql SQL语句
     * @return mixed PDOstatement || false
     */
    public function query($sql)
    {
        // SQL执行计时开始
        $startTime = microtime(true);
        
        // 获取连接
        $conn = $this->getConnector();
        
        /**
         * PDO statement
         *
         * @var \PDOStatement $statement
         */
        $statement = $conn->query($sql);
        
        // SQL执行统计
        $this->onQuery($sql, microtime(true) - $startTime);
        
        // 执行成功则返回
        if (false !== $statement) {
            $this->_relinkCounter = 0;
            $this->_lastStatement = $statement;
            return $statement;
        }
        
        // 执行不成功时，检测错误代码，如果是需要的错误代码，则进行重新连接
        $errNO = $conn->errorInfo()[1];
        if (in_array($errNO, self::RELINK_ERRNO_LIST) && ($this->_relinkCounter < self::RELINK_MAX)) {
            $this->_relinkCounter++;
            $this->close();
            $this->getConnector();
            return $this->query($sql);
        }
        
        // 记录SQL错误
        $this->onError(sprintf('QUERY FAILD:%s' . $sql));
        return false;
    }
    
    /**
     * 执行SQL 主要为写入操作
     *
     * @param string $sql SQL语句
     * @return int || false
     */
    public function exec($sql)
    {
        // SQL执行计时开始
        $interval = microtime(true);
        
        // 获取PDO连接
        $conn = $this->getConnector();
        
        /**
         * PDB query statement
         *
         * @var \PDOStatement $statement
         */
        $count = $conn->exec($sql);
        
        // SQL执行记录
        $this->onQuery($sql, microtime(true) - $interval);
        
        if ($count !== false) {
            $this->relinkCounter = 0;
            return true;
        }
        
        $errNO = $conn->errorInfo()[1];
        if (in_array($errNO, self::RELINK_ERRNO_LIST) && $this->relinkCounter < self::RELINK_MAX) {
            $this->relinkCounter++;
            $this->close();
            $this->getConnector();
            return $this->exec($sql);
        }
        
        // 记录SQL执行错误
        $this->onError(sprintf('EXEC FAILD:%s', $sql));
        return false;
    }
    
    /**
     * 获取插入语句的最后一个ID 必须有主键ID
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->getConnector()->lastInsertId();
    }
    
    /**
     * 返回调用当前查询后的结果集中的记录数
     *
     * @return int
     */
    public function rowCount()
    {
        if (!$this->lastStatement) {
            return 0;
        }
        return $this->lastStatement->rowCount();
    }
    
    /**
     * 查询并获取 一条结果集
     *
     * @param string $sql 查询的SQL语句
     * @return array
     */
    public function fetch($sql)
    {
        $statement = $this->query($sql);
        if ($statement === false) {
            return [];
        }
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * 查询并获取所有结果集
     *
     * @param string $sql 查询的SQL语句
     * @return array || null
     */
    public function fetchAll($sql)
    {
        $statement = $this->query($sql);
        if ($statement === false) {
            return [];
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * 关闭或者销毁实例和链接
     *
     * @return void
     */
    public function close()
    {
        if ($this->lastStatement) {
            $this->lastStatement = null;
        }
        $this->connector = null;
    }
    
    /**
     * 开始事务
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getConnector()->beginTransaction();
    }
    
    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit()
    {
        return $this->getConnector()->commit();
    }
    
    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->getConnector()->rollback();
    }
}
?>