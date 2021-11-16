<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name IDataSchema.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月4日上午12:38:08
 * @Class List
 * @Function List
 * @History King 2017年4月4日上午12:38:08 0 第一次建立该文件
 *          King 2017年4月4日上午12:38:08 1 上午修改
 *          King 2020年3月2日11:31 stable 1.0 审定
 */
namespace Tiny\Data;

/**
 * Data结构的接口
 *
 * @package Tiny.Data
 * @since 2013-11-28上午03:42:16
 * @final 2013-11-28上午03:42:16
 *        King 2020年3月2日11:31 stable 1.0 审定
 */
interface IDataSchema
{

    /**
     * 数据操作程序的构造函数 统一输入数组配置
     *
     * @param array $policy
     *        默认为空函数
     * @return void
     *
     */
    public function __construct(array $policy = []);

    /**
     * 返回连接后的句柄
     *
     * @return IDataSchema
     *
     */
    public function getConnector();
}
?>