<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name YamlParser.php
 * @author King
 * @version Beta 1.0
 * @Date 2020年2月27日下午5:37:29
 * @Description
 * @Class List
 *        1.YmlParser Yml配置解析器类
 * @History King 2020年2月27日下午5:37:29 第一次建立该文件
 *          King 2020年2月29日12:43 stable 1.0 审定
 *
 */
namespace Tiny\Config\Parser;

/**
 * yaml配置文件解析器
 *
 * @package Tiny.Config.Parser
 * @since 2020年2月27日下午5:38:36
 * @final 2020年2月27日下午5:38:36
 *        King 2020年2月29日12:43 stable 1.0 审定
 */
class YamlParser implements ParserInterface
{

    /**
     * 解析INI配置文件，解析异常会抛出异常并终止
     *
     * {@inheritdoc}
     * @see \Tiny\Config\Parser\ParserInterface::parse()
     */
    public function parse($fpath)
    {
        $contents = file_get_contents($fpath);
        $data = '';
        if (function_exists('yaml_parse'))
        {
            $data = yaml_parse($contents);
        }
        return $data;
    }
}
?>