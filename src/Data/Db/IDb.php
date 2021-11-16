<?php
/**
 * DB操作接口
 *
 * @copyright (C), 2013-, King.
 * @name IDb.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月4日下午12:12:36
 * @Class List
 *              1. IDb DB操作接口类
 * @History King 2017年4月4日下午12:12:36 0 第一次建立该文件
 *          King 2017年4月4日下午12:12:36 1 上午修改
 *          King 2020年3月2日11:31 stable 1.0 审定
 */
namespace Tiny\Data\Db;

use Tiny\Data\IDataSchema;

/**
 * 数据池DB数据源的接口类
 *
 * @package Tiny.Data.Db
 * @since 2013-11-28上午06:50:11
 * @final 2013-11-28上午06:50:11
 *        King 2020年3月2日11:31 stable 1.0 审定
 */
interface IDb extends IDataSchema
{

    /**
     * 查询事件
     *
     * @param string $msg
     *        查询内容
     * @param float $time
     * @return void
     */
    public function onQuery($msg, $time);

    /**
     * 错误发生事件
     *
     * @param string $msg
     *        错误语句
     * @return void
     */
    public function onError($msg);

    /**
     * 获取最近一条错误的内容
     *
     * @return string
     */
    public function getErrorMSg();

    /**
     * 获取最近一条错误的标示
     *
     * @return int
     *
     */
    public function getErrorNo();

    /**
     * 重载方法：执行 查询SQL
     *
     * @param string $sql
     *        SQL执行语句
     */
    public function query($sql);

    /**
     * 关闭数据库链接
     *
     * @return void
     */
    public function close();

    /**
     * 执行写SQL
     *
     * @param string $sql
     *        SQL语句
     * @return int|FALSE
     */
    public function exec($sql);

    /**
     * 获取最后一条插入的ID
     *
     * @return int
     */
    public function lastInsertId();

    /**
     * 查询并获取 一条结果集
     *
     * @param string $sql
     *        查询的SQL语句
     * @return array|NULL
     */
    public function fetch($sql);

    /**
     * 查询并获取所有结果集
     *
     * @param string $sql
     *        查询的SQL语句
     * @return array|NULL
     */
    public function fetchAll($sql);

    /**
     * 开始事务
     *
     * @return bool
     */
    public function beginTransaction();

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit();

    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollBack();
}
?>