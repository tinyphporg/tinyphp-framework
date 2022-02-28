<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Mysqli.php
 * @author King
 * @version 1.0
 * @Date: 2013-11-28上午06:55:47
 * @Description MYSQL操作类 MYSQLI扩展
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
 * MYSQL的MYSQLI扩展
 *
 * @package Tiny.Data.Db.Mysql
 * @since 2013-11-28上午06:56:26
 * @final 2013-11-28上午06:56:26
 *        King 2020年03月5日15:48:00 stable 1.0 审定稳定版本
 */
class Mysqli implements DbAdapterInterface
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
    const RELINK_ERRNO_LIST = [2006, 2013];
    
    /**
     * 配置数组
     *
     * @var array
     */
    protected $config;
    
    /**
     * 连接标示
     *
     * @var \Mysqli
     */
    protected $connector;
    
    /**
     * 重连计数器
     *
     * @var int
     */
    protected $relinkCounter = 0;
    
    /**
     * 最后一次SQL返回的statement对象
     *
     * @var \mysqli
     */
    protected $lastStatement = false;
    
    /**
     * 构造函数
     *
     * @param array $policy 默认为空函数
     *
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    /**
     * 记录查询详情
     *
     * @param string $sql 查询内容
     * @param float $time
     */
    public function onQuery($sql, $interval)
    {
        return Db::addQuery($sql, $interval, __CLASS__);
    }
    
    /**
     * 错误发生事件
     *
     * @param string $errmsg 错误信息
     */
    public function onError($errmsg)
    {
        if ($this->connector) {
            $errmsg = sprintf('%s %s:%s', $this->connector->errno, $this->connector->error, $errmsg);
        }
        throw new MysqlException($errmsg);
    }
    
    /**
     * 开始连接
     *
     * @return \Mysqli
     */
    public function getConnector()
    {
        if ($this->connector) {
            return $this->connector;
        }
        
        // 计时开始
        $startTimeline = microtime(true);
        $config = $this->config;
        $this->connector = new \Mysqli($config['host'], $config['user'], $config['password'], $config['dbname'], $config['port']);
        if ($this->connector->connect_error) {
            throw new MysqlException('Db connection failed:' . $this->connector->connect_error);
        }
        // 设置编码
        $this->connector->set_charset($config['charset']);
        
        // 连接计时
        $this->onQuery(sprintf('db connection %s@%s:%d ...', $config['user'], $config['host'], $config['port']), microtime(true) - $startTimeline);
        return $this->connector;
    }
    
    /**
     * 获取最近一条错误的内容
     *
     * @return string
     */
    public function getErrorMSg()
    {
        return $this->getConnector()->error;
    }
    
    /**
     * 获取最近一条错误的标示
     *
     * @return int
     */
    public function getErrorNo()
    {
        return $this->getConnector()->errno;
    }
    
    /**
     * 关闭或者销毁实例和链接
     *
     */
    public function close()
    {
        if ($this->connector) {
            $this->connector->close();
        }
        $this->connector = null;
    }
    
    /**
     * 重载方法：执行 SQL
     *
     * @param string $sql
     * @return mixed
     */
    public function query($sql)
    {
        // SQL执行计时开始
        $startTime = microtime(true);
        
        $conn = $this->getConnector();
        $this->lastStatement = $conn->query($sql);
        
        // SQL执行统计
        $this->onQuery($sql, microtime(true) - $startTime);
        
        if (false !== $this->lastStatement) {
            $this->relinkCounter = 0;
            return $this->lastStatement;
        }
        
        if (in_array($conn->errno, self::RELINK_ERRNO_LIST) && $this->relinkCounter < self::RELINK_MAX) {
            $this->relinkCounter++;
            $this->close();
            $this->getConnector();
            return $this->query($sql);
        }
        $this->onError(sprintf('QUERY FAILD:%s', $sql));
        return false;
    }
    
    /**
     * 执行写操作SQL
     *
     * @param string $sql SQL语句
     * @return int
     */
    public function exec($sql)
    {
        // SQL执行计时开始
        $startTime = microtime(true);
        $mret = $this->query($sql);
        
        // SQL执行统计
        $this->onQuery($sql, microtime(true) - $startTime);
        
        if ($mret) {
            return $this->getConnector()->affected_rows;
        }
        return 0;
    }
    
    /**
     * 获取最后一条插入的ID
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->getConnector()->insert_id;
    }
    
    /**
     * 返回调用当前查询后的结果集中的记录数
     *
     * @return int
     */
    public function rowsCount()
    {
        if (!$this->lastStatement) {
            return 0;
        }
        if ($this->lastStatement) {
            return $this->getConnector()->affected_rows;
        }
        return $this->lastStatement->num_rows;
    }
    
    /**
     * 查询并获取 一条结果集
     *
     * @param string $sql SQL语句
     * @return array
     */
    public function fetch($sql)
    {
        $statement = $this->query($sql);
        if (true === $statement) {
            return [];
        }
        $row = $statement->fetch_array(MYSQLI_ASSOC);
        if (null === $row) {
            return [];
        }
        return $row;
    }
    
    /**
     * 查询并获取所有结果集
     *
     * @param string $sql SQL语句
     * @return array
     */
    public function fetchAll($sql)
    {
        $statement = $this->query($sql);
        if (true === $statement) {
            return [];
        }
        return $statement->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * 开始事务
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getConnector()->begin_transaction(true);
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