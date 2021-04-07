<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name IParser.php
 * @author King
 * @version Beta 1.0
 * @Date 2020年2月26日上午11:19:19
 * @Description 配置解析器接口 规范入口形式
 * @Class List 1.配置解析器接口
 * @History King 2020年2月26日上午11:19:19 第一次建立该文件
 *          King 2020年2月26日上午11:19:19 修改
 *          King 2020年2月29日12:43 stable 1.0.01 审定
 */
namespace Tiny\Config\Parser;

/**
 * 解析器接口
 *
 * @package Tiny.Config.Parser
 * @since 2020年2月26日上午11:20:22
 * @final 2020年2月26日上午11:20:22
 *        King 2020年2月29日12:43 stable 1.0.01 审定
 */
interface IParser
{

    /**
     * 解析配置文件
     *
     * @param string $path
     */
    public function parse(string $path);
}
?>