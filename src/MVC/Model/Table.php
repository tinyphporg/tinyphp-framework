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
 * @author King
 * @package Model
 * @since 2013-3-31下午02:10:22
 * @final 2013-3-31下午02:10:22}
 */
abstract class Table extends Base
{

    /**
     * 操作的表名
     *
     * @var string
     */
    protected $_tableName = NULL;

    /**
     * 默认的写数据库实例Id
     *
     * @var string
     */
    protected $_writeId = 'default';

    /**
     * 默认的读数据库实例ID
     *
     * @var string
     */
    protected $_readId = NULL;

    /**
     * Data写实例
     *
     * @var Db
     */
    protected $_writeSchema;

    /**
     * Data读实例
     *
     * @var Db
     */
    protected $_readSchema;

    /**
     * 构造函数 初始化获取的数据库连接实例id
     *
     * @param string $tableName
     *        操作的表名称
     * @param string $writeId
     *        写数据库的Data ID
     * @param string|array $readId
     *        读数据库的Data ID
     * @return void
     */
    public function __construct($tableName = NULL, $writeId = 'default', $readId = NULL)
    {
        $this->_writeId = $writeId;
        $this->_readId = $readId;
        if (!$tableName)
        {
            throw new ModelException('Model.Table实例化失败，必须设置tablename');
        }
        $this->_tableName = (string)$tableName;
    }

    /**
     * 执行SQL语句 主要为写操作 主从模式下，只会执行主库
     *
     * @param string $sql
     *        SQL执行语句
     * @param array ...$param
     *        绑定可变参数数组
     * @return bool
     */
    public function exec($sql, ...$param)
    {
        return $this->getWriteSchema()->exec($sql, ...$param);
    }

    /**
     * 执行SQL并返回结果集的第一行(一维数组)
     *
     * @param string $sql
     *        SQL查询语句
     * @return array
     */
    public function fetch($sql, ...$param)
    {
        return $this->getReadSchema()->fetch($sql, ...$param);
    }

    /**
     * 执行SQL查询 并返回所有结果集
     *
     * @param string $sql
     *        SQL查询语句
     * @param string $param
     *        可变参数数组
     * @return array
     */
    public function fetchAll($sql, ...$param)
    {
        return $this->getReadSchema()->fetchAll($sql, ...$param);
    }

    /**
     * 获取以指定字段名为key的二维数组结果集
     * 注意： 必须有唯一索引或者主键，如果有重复值，则数组会被替代，导致结果集数目不准确。
     *
     * @param string $sql
     *        SQL语句
     * @param string $columnName
     *        字段名 为空时默认取第一个字段
     * @param array $param
     *        可变参数数组
     * @return array
     */
    public function fetchAssoc($sql, $columnName = NULL, ...$param)
    {
        return $this->getReadSchema()->fetchAssoc($sql, $columnName, ...$param);
    }

    /**
     * 返回结果集中第一列的所有值(一维数组)
     *
     * @param string $sql
     *        SQL查询语句
     * @param array $param
     *        可变参数数组
     * @return array
     */
    public function fetchColumn($sql, $columnName = NULL, ...$param)
    {
        return $this->getReadSchema()->fetchColumn($sql, $columnName, ...$param);
    }

    /**
     * 执行SQL 返回第一行指定单元格的值
     *
     * @param string $sql
     *        SQL查询语句
     * @param string $columnName
     *        字段名
     * @param int $index
     *        索引值
     * @param array $param
     *        可变参数数组
     * @return array
     */
    public function fetchCeil($sql, $columnName = NULL, $index = 0, ...$param)
    {
        return $this->getReadSchema()->fetchCeil($sql, $columnName, $index, ...$param);
    }

    /**
     * 获取第一列第一个单元格的值
     *
     * @param string $sql
     *        SQL查询语句
     * @param array $param
     *        绑定参数数组
     * @return string || int
     */
    public function fetchFirstCeil($sql, ...$param)
    {
        return $this->getReadSchema()->fetchFirstCeil($sql, ...$param);
    }

    /**
     * 返回最后执行 Insert() 操作时表中有 auto_increment 类型主键的值
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->getWriteSchema()->getLastInsertId();
    }

    /**
     * 最后 DELETE UPDATE 语句所影响的行数
     *
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->getWriteSchema()->rowsCount();
    }

    /**
     * 返回调用当前查询后的结果集中的记录数
     *
     * @return int
     */
    public function getRowCount()
    {
        return $this->getReadSchema()->rowsCount();
    }

    /**
     * 开始事务
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getWriteSchema()->beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit()
    {
        return $this->getWriteSchema()->commit();
    }

    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->getReadSchema()->rollBack();
    }

    /**
     * 返回MYSQL系统中当前所有可用的数据库
     *
     * @return array
     */
    public function getDbs()
    {
        return $this->getReadSchema()->getDbs();
    }

    /**
     * 返回数据库中所有的表,如果为空则返回当前数据库中所有的表名
     *
     * @return array
     */
    public function getTables()
    {
        return $this->getReadSchema()->getTables();
    }

    /**
     * 返回指定表的所有字段名
     *
     * @return array
     */
    public function getTableColumns()
    {
        return $this->getReadSchema()->getTableColumns($this->_tableName);
    }

    /**
     * 获取读的DB实例
     *
     * @return Db
     */
    public function getReadSchema()
    {
        if ($this->_readSchema)
        {
            return $this->_readSchema;
        }
        // read如果没有设置 直接返回写入的writekey
        if (!$this->_readId)
        {
            $this->_readSchema = $this->getWriteSchema();
        }
        elseif (is_array($this->_readId))
        {
            $readIndex = rand(0, count($this->_readId) - 1);
            $this->_readSchema = $this->_getSchema($this->_readId[$readIndex]);
        }
        else
        {
            $this->_readSchema = $this->_getSchema($this->_readId);
        }
        return $this->_readSchema;
    }

    /**
     * 获取读的Db Schema实例
     *
     * @return Db
     */
    public function getWriteSchema()
    {
        if (!$this->_writeSchema)
        {
            $this->_writeSchema = $this->_getSchema($this->_writeId);
        }
        return $this->_writeSchema;
    }

    /**
     * 获取数据库操作实例
     *
     * @param string $id
     *        数据源操作ID
     * @return Db
     */
    protected function _getSchema($id)
    {
        $schema = $this->data->getData($id);
        if (!$schema instanceof Db)
        {
            throw new ModelException(sprintf('Model.Db获取失败:ID:%s并非 继承成自 Tiny\Data\Db\Db实例!', $id));
        }
        return $schema;
    }
}
?>