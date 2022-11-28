<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name Db.php
 * @author King
 * @version Beta 1.0
 * @Date Sat Jan 07 23:43:54 CST 2012
 * @Description 数据库操作类
 * @Class List
 *        1.Db 数据库操作类
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Sat Jan 07 23:43:54 CST 2012 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\Model;

use Tiny\Data\Db\Db;

/**
 * 数据库的表操作模型
 *
 * @package Model
 * @since 2013-3-31下午02:10:22
 * @final 2013-3-31下午02:10:22}
 */
abstract class Table extends Model
{
    
    /**
     * 操作的表名
     *
     * @var string
     */
    protected $tableName = '';
    
    /**
     * 默认的写数据库实例Id
     *
     * @var string
     */
    protected $writeId = 'default';
    
    /**
     * 默认的读数据库实例ID
     *
     * @var string
     */
    protected $readId;
    
    /**
     * Db写实例
     *
     * @var Db
     */
    protected $writeDb;
    
    /**
     * Db读实例
     *
     * @var Db
     */
    protected $readDb;
    
    /**
     * 构造函数 初始化获取的数据库连接实例id
     *
     * @param string $tableName 操作的表名称
     * @param string $writeId 写数据库的Data ID
     * @param string|array $readId 读数据库的Data ID
     * @return void
     */
    public function __construct(string $tableName = '', string $writeId = 'default', $readId = '')
    {
        $this->writeId = $writeId;
        $this->readId = $readId;
        if ($tableName) {
            $this->tableName = $tableName;
        }
        if (!$this->tableName) {
            throw new ModelException('Model.Table实例化失败，必须设置tableName');
        }
        $this->tableName = (string)$tableName;
    }
    
    /**
     * 执行SQL语句 主要为写操作 主从模式下，只会执行主库
     *
     * @param string $sql SQL执行语句
     * @param array ...$param 绑定可变参数数组
     * @return bool
     */
    public function exec($sql, ...$param)
    {
        return $this->getWriteDb()->exec($sql, ...$param);
    }
    
    /**
     * 执行SQL并返回结果集的第一行(一维数组)
     *
     * @param string $sql SQL查询语句
     * @return array
     */
    public function fetch($sql, ...$param)
    {
        return $this->getReadDb()->fetch($sql, ...$param);
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
        return $this->getReadDb()->fetchAll($sql, ...$param);
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
        return $this->getReadDb()->fetchAssoc($sql, $columnName, ...$param);
    }
    
    /**
     * 返回结果集中第一列的所有值(一维数组)
     *
     * @param string $sql SQL查询语句
     * @param array $param 可变参数数组
     * @return array
     */
    public function fetchColumn($sql, $columnName = null, ...$param)
    {
        return $this->getReadDb()->fetchColumn($sql, $columnName, ...$param);
    }
    
    /**
     * 执行SQL 返回第一行指定单元格的值
     *
     * @param string $sql SQL查询语句
     * @param string $columnName 字段名
     * @param int $index 索引值
     * @param array $param 可变参数数组
     * @return array
     */
    public function fetchCeil($sql, $columnName = null, $index = 0, ...$param)
    {
        return $this->getReadDb()->fetchCeil($sql, $columnName, $index, ...$param);
    }
    
    /**
     * 获取第一列第一个单元格的值
     *
     * @param string $sql SQL查询语句
     * @param array $param 绑定参数数组
     * @return string || int
     */
    public function fetchFirstCeil($sql, ...$param)
    {
        return $this->getReadDb()->fetchFirstCeil($sql, ...$param);
    }
    
    /**
     * 返回最后执行 Insert() 操作时表中有 auto_increment 类型主键的值
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->getWriteDb()->getLastInsertId();
    }
    
    /**
     * 最后 DELETE UPDATE 语句所影响的行数
     *
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->getWriteDb()->rowsCount();
    }
    
    /**
     * 返回调用当前查询后的结果集中的记录数
     *
     * @return int
     */
    public function getRowCount()
    {
        return $this->getReadDb()->rowsCount();
    }
    
    /**
     * 开始事务
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getWriteDb()->beginTransaction();
    }
    
    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit()
    {
        return $this->getWriteDb()->commit();
    }
    
    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->getReadDb()->rollBack();
    }
    
    /**
     * 返回MYSQL系统中当前所有可用的数据库
     *
     * @return array
     */
    public function getDbs()
    {
        return $this->getReadDb()->getDbs();
    }
    
    /**
     * 返回数据库中所有的表,如果为空则返回当前数据库中所有的表名
     *
     * @return array
     */
    public function getTables()
    {
        return $this->getReadDb()->getTables();
    }
    
    /**
     * 返回指定表的所有字段名
     *
     * @return array
     */
    public function getTableColumns()
    {
        return $this->getReadDb()->getTableColumns($this->_tableName);
    }
    
    /**
     * 获取读的DB实例
     *
     * @return Db
     */
    public function getReadDb()
    {
        if ($this->readDb) {
            return $this->readDb;
        }
        // read如果没有设置 直接返回写入的writekey
        if (!$this->readId) {
            $this->readDb = $this->getWriteDb();
        } elseif (is_array($this->readId)) {
            $readIndex = rand(0, count($this->readDb) - 1);
            $this->readDb = $this->getDb($this->readId[$readIndex]);
        } else {
            $this->readDb = $this->getDb($this->readId);
        }
        return $this->readDb;
    }
    
    /**
     * 获取读的Db Schema实例
     *
     * @return Db
     */
    public function getWriteDb()
    {
        if (!$this->writeDb) {
            $this->writeDb = $this->getDb($this->writeId);
        }
        return $this->writeDb;
    }
    
    /**
     * 获取数据库操作实例
     *
     * @param string $id 数据源操作ID
     * @return Db
     */
    protected function getDb($id)
    {
        $db = $this->data->getDataSource($id);
        if (!$db instanceof Db) {
            throw new ModelException(sprintf('Failed to get the database operation instance: %s does not implement the interface named %s!', get_class($db), Db::class));
        }
        return $db;
    }
}
?>