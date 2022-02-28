<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name DbAdapterInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午1:57:04
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午1:57:04 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Data\Db;

/**
 * Db适配器接口
 *
 * @package Tiny.Data.Db
 * @since 2022年2月10日上午11:31:28
 * @final 2022年2月10日上午11:31:28
 */
interface DbAdapterInterface
{
    
    /**
     * 查询事件
     *
     * @param string $msg 查询内容
     * @param float $time
     * @return void
     */
    public function onQuery($msg, $time);
    
    /**
     * 错误发生事件
     *
     * @param string $msg 错误语句
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
     * @param string $sql SQL执行语句
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
     * @param string $sql SQL语句
     * @return int|false
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
     * @param string $sql 查询的SQL语句
     * @return array
     */
    public function fetch($sql);
    
    /**
     * 查询并获取所有结果集
     *
     * @param string $sql 查询的SQL语句
     * @return array
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