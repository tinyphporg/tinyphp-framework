<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name JSONParser.php
 * @author King
 * @version Beta 1.0
 * @Date 2020年2月28日下午12:10:54
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年2月28日下午12:10:54 第一次建立该文件
 *          King 2020年2月28日下午12:10:54 stable 1.0 审定
 *
 */
namespace Tiny\Config\Parser;

/**
 * JSON配置解析器类
 *
 * @package Tiny.Config.Parser
 * @since 2020年2月28日下午12:11:43
 * @final 2020年2月28日下午12:11:43
 *        King 2020年2月29日12:43 stable 1.0 审定
 */
class JSONParser implements ParserInterface
{

    /**
     * 解析JSON文件
     *
     * @see \Tiny\Config\Parser\ParserInterface::parse()
     */
    public function parse($fpath)
    {
        $content = file_get_contents($fpath);
        $data = json_decode($content, true);
        $errno = json_last_error();
        if ($errno != JSON_ERROR_NONE)
        {
            $errmsg = json_last_error_msg();
            throw new ParserException(sprintf("Failed to parse %s: %s", $fpath, $errmsg));
        }
        return $data;
    }
}
?>