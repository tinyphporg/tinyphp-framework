<?php
/**
 *
 * @Copyright (C), 2013-, King.
 * @Name  Url.php
 * @Author  King
 * @Version  1.0
 * @Date: 2014-2-8上午1:00:17
 * @Description
 * @Class List
 *      1.
 *  @Function List
 *   1.
 *  @History
 *      <author>    <time>                        <version >   <desc>
 *        King      2014-2-8上午1:00:17       第一次建立该文件
 *        King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\View\Engine\Template;

use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\View\Engine\Template\IPlugin;
use Tiny\MVC\View\Engine\Template;
use Tiny\MVC\Router\Router;
use Tiny\Tiny;

/**
* url辅助类
* @package Tiny.MVC.View.Helper
* @since 2014-2-8上午1:19:01
* @final  2014-2-8上午1:19:01
*/
class Url implements IPlugin
{
    /**
     * 当前template实例
     * 
     * @var Template
     */
    protected $_template;
    
    /**
     * 当前URL插件的配置数组
     * 
     * @var array
     */
    protected $_templateConfig;
    /**
     * 可解析的标签列表
     * @var array
     */
    const PARSE_TAG_LIST = ['url'];
    
    /**
     * 实现接口
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Template\IPlugin::setTemplateConfig()
     */
    public function setTemplateConfig(Template $template, array $config)
    {
        $this->_template = $template;
        $this->_templateConfig = $config;
    }
    
    /**
     * 解析前发生
     *
     * @param string $template 解析前的模板字符串
     * @return FALSE|string
     */
    public function onPreParse($template)
    {
        return FALSE;
    }
    
    /**
     * 解析URL的闭合标签
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Template\IPlugin::onParseCloseTag()
     */
    public function onParseCloseTag($tagName)
    {
        if(!in_array($tagName, self::PARSE_TAG_LIST))
        {
            return FALSE;
        }
        return '';
    }
    
    /**
     * 解析URL标签
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Template\IPlugin::onParseTag()
     */
    public function onParseTag($tagName, $tagBody, $extra = NULL)
    {
        if(!in_array($tagName, self::PARSE_TAG_LIST))
        {
            return FALSE;
        }
        $paramText = explode(',', $tagBody);
        $params = [];
        $isRewrite = ($extra == 'r') ? TRUE : FALSE;
        foreach($paramText as $ptext)
        {
            $ptext = trim($ptext);
            if(preg_match('/\s*(.+?)\s*=\s*(.*)\s*/i', $ptext, $out))
            {
                $params[$out[1]] = $out[2];
            }
        }
        $router = Tiny::getApplication()->getRouter();
        if($router)
        {
            return $router->rewriteUrl($params, $isRewrite);
        }
        return '';
    }
    
    /**
     * 解析后发生
     *
     * @param string $template 解析后的模板字符串
     * @return FALSE|string
     */
    public function onPostParse($template)
    {
        return FALSE;
    }
}
?>