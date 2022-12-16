<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name SyntaxParser.php
 * @author King
 * @version stable 2.0
 * @Date 2022年12月6日下午5:09:28
 * @Class List class
 * @Function List function_container
 * @History King 2022年12月6日下午5:09:28 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Engine\Tagger\Parser;


use Tiny\MVC\Router\Router;
use Tiny\MVC\View\Engine\Tagger\Tagger;
use Tiny\MVC\View\ViewManager;

/**
*  基本语法和逻辑流程判断的标签解析
*  
* @package Tiny.MVC.View.Engine.Tagger.Parser
* 
* @since 2022年12月16日下午8:05:48
* @final 2022年12月16日下午8:05:48
*/
class SyntaxParser implements ParserInterface
{
    /**
     * 解析完成的标签列表
     * 
     * @var array
     */
    protected  $parsedTemplateTags = [];
    
    /**
     * 视图管理器实例
     * 
     * @var ViewManager
     */
    protected $viewManager;
    
    /**
     * 注入视图实例管理器
     * 
     * @param ViewManager $viewManager
     */
    public function  __construct(ViewManager $viewManager)
    {
        $this->viewManager = $viewManager;   
    }
    
    /**
     * 解析前发生
     *
     * @param string $template 解析前的模板字符串
     * @return false|string
     */
    public function onPreParse($template)
    {
        $this->parsedTemplateTags = [];
        return false;
    }
    
    /**
     * 调用插件事件解析闭合标签
     *
     * @param string $tagName
     * @return string|false
     */
    public function onParseCloseTag($tagName, $namespace = '')
    {
        if ($namespace !== '') {
            return false;
        }
        switch ($tagName) {
            case 'loop':
                return '<? } ?>';
            case 'foreach':
                return '<? } ?>';
            case 'for':
                return '<? } ?>';
            case 'if':
                return '<? } ?>';
            case 'else':
            case 'elseif':
            case 'eval':
            case 'template':
            case 'url':
            case 'date':
            default:
                return false;
        }
    }
    
    /**
     * 调用插件事件解析tag
     *
     * @param string $tagName  标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加信息
     * @return string|boolean 返回解析成功的字符串  false时没有找到解析成功的插件 或者解析失败
     */
    public function onParseTag($tagName, $namespace = '', array $params = [])
    {
        // <templateid:template />
        if ('template' === $tagName) {
            return $this->parseTemplateTag($namespace, $params);
        }
        
        if ($namespace !== '') {
            return false;
        }
        
        switch ($tagName) {
            case 'loop':
                return $this->parseLoopsection($params);
            case 'foreach':
                return $this->parseLoopsection($params);
            case 'for':
                return $this->parseForTag($params);
            case 'if':
                return $this->parseIfTag($params);
            case 'else':
                return $this->parseElseTag($params);
            case 'elseif':
                return $this->parseElseIfTag($params);
            case 'eval':
                return $this->parseEvalTag($params);
            case 'date':
                return $this->parseDateTag($params);
            case 'url':
                return $this->parseUrlTag($params);
        }
        
        return false;
        
    }
    
    /**
     * 解析完成后发生
     *
     * @param string $template 解析后的模板字符串
     * @return false|string
     */
    public function onPostParse($template)
    {
        $this->onPostParseTemplateTag($template);
        return false;
    }
    
    /**
     * 解析遍历数组循环
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|false;
     */
    protected function parseLoopsection(array $params)
    {
        $tagBody  = $params[0];
        $tagNodes = explode(' ', $tagBody);
        $nodeNum = count($tagNodes);
        if (2 == $nodeNum || 3 == $nodeNum) {
            return (3 == $nodeNum) ? sprintf("<? foreach(%s as %s => %s) { ?>", $tagNodes[0], $tagNodes[1], $tagNodes[2]) : sprintf("<? foreach(%s as %s) { ?>", $tagNodes[0], $tagNodes[1]);
        }
        return false;
    }
    
    /**
     * 解析For标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加标识
     * @return string
     */
    protected function parseForTag(array $params)
    {
        return sprintf('<? for ( %s ) { ?>', $params[0]);
    }
    
    /**
     * 解析Else标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 标签标识
     * @return string
     *
     */
    protected function parseElseTag(array $params)
    {
        return '<? } else { ?>';
    }
    
    /**
     * 解析if标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|false;
     */
    protected function parseIfTag(array $params)
    {
        return sprintf('<?  if( %s ) { ?>', $params[0]);
    }
    
    /**
     * 解析elseif标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|false;
     */
    protected function parseElseIfTag(array $params)
    {
        return sprintf('<? } elseif( %s ) { ?>', $params[0]);
    }
    
    /**
     * 解析eval标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|false;
     */
    protected function parseEvalTag(array $params)
    {
        return sprintf('<? %s ?>', $params[0]);
    }
    
    /**
     * 解析template标签
     *
     * 解析出的模板路径，会通过View的单例调用对应的模板引擎实例->fetch()内容替换
     * 该模板引擎实例 是继承了Base的PHP/Template 直接替换为include运行, 可以共享变量空间。
     *
     * @param string $tagBody 解析的模板路径
     * @param boolean $isCloseTag 是否为闭合标签
     * @return string
     */
    protected function parseTemplateTag($namespace, array $params)
    {
        // 命名空间即为模板空间
        $templateId = $namespace;
        $tfile = key_exists('file', $params) ? $params['file'] : ($params[1] ? $params[1] : $params[0]);
        
        $name = (string)$params['name'];
        if (key_exists('id', $params)) {
            $templateId =  (string)$params['id'];
        }
        
        $inject = (string)$params['inject'];
        if ($inject === 'false') {
            $inject = false;
        }
        
        $content = '';        
        if (!$name && !$tfile) {
            return '';
        }
        
        if ($tfile) {
            $viewEngine = $this->viewManager->getEngineByPath($tfile);
            if (!$viewEngine) {
                return '';
            }
        }
        
        if (!$templateId && $this->fetchingTemplateId) {
            $templateId = $this->fetchingTemplateId;
        }
        
        if (!$name) {
            return ($viewEngine instanceof Tagger)
            ? sprintf('<?php include  $this->getCompiledFile("%s", "%s");?>', $tfile, $templateId)
            :  sprintf('<?php echo $this->view->fetch("%s", $this->fetchingVariables, "%s");?>', $tfile, $templateId);
        }
        
        // 占位符 一旦没有设置inject参数，则内容输出在此
        $content = '';
        if ($name && !in_array($name, $this->parsedTemplateTags)) {
            $content = sprintf("<!--template.first.%s-->", $name);
            $this->parsedTemplateTags[] = $name;
        }
        
        // 如有设置inject参数 则直接输出占位符
        if ($name && $inject) {
            $content = sprintf("<!--template.content.%s-->", $name);
        }
        
        if ($tfile) {
            $isSelf = $viewEngine instanceof Tagger ? 'true' : 'false';
            $content .= sprintf('<? echo $this->fetchTemplate("%s", "%s", "%s", %s);?>', $tfile, $templateId, $name,  $isSelf);
        }
        return $content;
    }
    
    /**
     * 解析 template的占位符
     *
     * @param string $template 模板内容
     */
    protected function onPostParseTemplateTag(&$template)
    {
        
        // 没有带有name属性的templates时 无需处理
        if (!$this->parsedTemplateTags) {
            return;
        }
        
        // 当所有具备相同name属性的template tag 都没有设置inject=true属性时，则内容将自动输出在第一个template tag附近
        foreach ($this->parsedTemplateTags as $name) {
            $replaceStr = '';
            $templateTag = sprintf('<!--template.content.%s-->', $name);
            if (strpos($template, $templateTag) === false) {
                $replaceStr = $templateTag;
            }
            $template = str_replace(sprintf('<!--template.first.%s-->', $name), $replaceStr, $template);
        }
        $this->parsedTemplateTags = [];
    }
    
    /**
     * 解析date标签
     *
     * @param string $tagBody 解析的标签设置
     * @param string $extra 附加信息
     * @return string
     */
    protected function parseDateTag(array $params)
    {
        $timestamp = key_exists('time', $params) ? $params['time'] : ($params[1] ?: time());
        $format = key_exists('format', $params) ? $params['format'] : ($params[2] ?: 'Y-m-d H:i:s');
        if (!is_int($timestamp)) {
            $timestamp = sprintf('strtotime("%s")', $timestamp);
        }
        return sprintf('<? echo date("%s", %d);?>', $format, $timestamp);
    }
    
    /**
     * 解析URL标签
     * <:url controller="main" action="index"  page="" />
     *
     *
     * @param string $tagBody 解析的标签主体信息
     * @param string $extra 附加信息
     * @return string
     */
    protected function parseUrlTag(array $params)
    {
        $paramText = explode(',', $params[0]);
        $params = [];
        $isRewrite = ($extra == 'r') ? true : false;
        foreach ($paramText as $ptext) {
            $ptext = trim($ptext);
            if (preg_match('/\s*(.+?)\s*=\s*(.*)\s*/i', $ptext, $out)) {
                $params[$out[1]] = $out[2];
            }
        }
        $router = $this->app->get(Router::class);
        if ($router) {
            return $router->rewriteUrl($params, $isRewrite);
        }
        return '';
    }
}
?>