<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name TagParser.php
 * @author King
 * @version stable 2.0
 * @Date 2022年6月3日下午1:53:05
 * @Class List class
 * @Function List function_container
 * @History King 2022年6月3日下午1:53:05 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Engine\Template;
 
/**
* 
* @package namespace
* @since 2022年6月3日下午1:54:51
* @final 2022年6月3日下午1:54:51
*/
trait TagAttributesParser
{
    /**
     * 解析成属性对
     * @param string $content
     */
    protected static function parseAttr($content)
    {
        if (!preg_match("/([a-z][a-z0-0]*)\s*=\s*(('.*?')|(\".*?\")|([0-9]+))(,\s+([a-z][a-z0-0]*)\s*=\s*(('.*?')|(\".*?\")|([0-9]+)))*/", $content)) {
            return false;
        }
        if (!preg_match_all("/([a-z][a-z0-0]*)\s*=\s*(('.*?')|(\".*?\")|([0-9]+))*/", $content, $matchs, PREG_SET_ORDER)) {
            return false;
        }
        $attrs = [];
        foreach ($matchs as $match) {
            $key = $match[1];
            $val = $match[2];
            $attrs[$key] = trim($val, "'\"");
        }
        return $attrs;
    }
    
    /**
     * 解析成数组
     * @param string $content
     */
    protected static function parseArray($content)
    {
        
    }
}
?>