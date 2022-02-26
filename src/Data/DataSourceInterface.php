<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name DataSourceInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月12日下午1:54:40
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月12日下午1:54:40 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Data;

/**
 * 数据源接口
 *
 * @package Tiny.Data
 * @since 2022年2月9日上午11:27:35
 * @final 2022年2月9日上午11:27:35
 */
interface DataSourceInterface
{
    
    /**
     * 构造函数 输入数据源配置数组
     *
     * @param array $config
     */
    public function __construct(array $config = []);
    
    /**
     * 返回连接后的数据源连接器
     *
     * @return mixed
     *
     */
    public function getConnector();
    
    /**
     * 通过魔法函数访问连接器的函数
     *
     * @param string $method
     * @param array $params
     */
    public function __call(string $method, array $params);
}
?>