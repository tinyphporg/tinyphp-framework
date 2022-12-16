<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name ParserInterface.php
 * @author King
 * @version stable 2.0
 * @Date 2022年12月6日下午4:20:40
 * @Class List class
 * @Function List function_container
 * @History King 2022年12月6日下午4:20:40 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Engine\Tagger\Parser;

/**
* 解析器接口
* 
* @package namespace
* @since 2022年12月6日下午4:28:32
* @final 2022年12月6日下午4:28:32
*/
interface ParserInterface 
{
    /**
     * 解析前发生
     *
     * @param string $template 解析前的模板字符串
     * @return false|string
     */
    public function onPreParse($template);
    
    /**
     * 调用插件事件解析闭合标签
     *
     * @param string $tagName
     * @return string|false
     */
    public function onParseCloseTag($tagName, $namespace = '');
    
    /**
     * 调用插件事件解析tag
     *
     * @param string $tagName  标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加信息
     * @return string|boolean 返回解析成功的字符串  false时没有找到解析成功的插件 或者解析失败
     */
    public function onParseTag($tagName, $namespace = '', array $params = []);
    
    /**
     * 解析完成后发生
     *
     * @param string $template 解析后的模板字符串
     * @return false|string
     */
    public function onPostParse($template);
}

?>