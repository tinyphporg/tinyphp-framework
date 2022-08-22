<?php
/**
 *
 * @copyright (C), 2011-, King.
 * @Name: Template.php
 * @Author: King
 * @Version: Beta 1.0
 * @Date: 2013-5-25上午08:21:54
 * @Description:
 * @Class List:
 *        1.
 * @Function List:
 *           1.
 * @History: <author> <time> <version > <desc>
 *           King 2013-5-25上午08:21:54 Beta 1.0 第一次建立该文件
 *           King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\ViewException;
use Tiny\MVC\View\Engine\Template\TagAttributesParser;
use Tiny\MVC\Router\Router;

define('TINY_IS_IN_VIEW_ENGINE_TEMPLATE', true);

/**
 * 简单的解析引擎
 *
 * @package Tiny\MVC\View\Engine
 * @since 2013-5-25上午08:21:38
 * @final 2013-5-25上午08:21:38
 */
class Template extends ViewEngine
{
    use TagAttributesParser;
    
    /**
     *
     * @autowired
     * @var \Tiny\MVC\Application\ApplicationBase
     */
    protected $app;
    
    /**
     *
     * @autowired
     * @var \Tiny\MVC\Response\Response
     */
    protected $response;
    
    /**
     * 插件实例
     *
     * @var array
     */
    protected $pluginInstances = [];
    
    /**
     * 匹配变量的正则
     *
     * @var string
     */
    const REGEXP_VARIABLE = "\@?\\\$[a-zA-Z_\-]\w*(?:(?:\-\>[a-zA-Z_\-]\w*)?(?:\[[\w\.\"\'\[\]\$\-]+\])?)*";
    
    /**
     * 匹配标签里变量标识的正则
     *
     * @var string
     */
    const REGEXP_VARIABLE_TAG = "\<\?=(\@?\\\$[a-zA-Z_\-]\w*(?:(?:\-\>[a-zA-Z_\-]\w*)?(?:\[[\w\.\"\'\[\]\$\-]+\])?)*)\?\>";
    
    /**
     * 匹配常量的正则
     *
     * @var string
     */
    const REGEXP_CONST = "\{((?!else)[\w]+)\}";
    
    /**
     * 解析中的模板标签
     *
     * @var array
     */
    protected $parsedTemplateTags = [];
    
    /**
     * 存放的模板标签解析内容数组
     *
     * @var array
     */
    protected $templateTagContents = [];
    
    /**
     * 获取模板解析后的文件路径
     *
     * @param string $file 文件路径
     * @param bool $isAbsolute 是否绝对位置
     * @return string 编译后的文件路径
     */
    public function getCompiledFile($tpath, $templateId = null)
    {
        $tpath = preg_replace('/\/+/', '/', $tpath);
        $pathinfo = $this->getTemplateRealPath($tpath, $templateId);
        if (!$pathinfo) {
            throw new ViewException(sprintf("viewer error: the template %s is not exists!", $tpath));
        }
        
        $tfile = $pathinfo['path'];
        $tfilemtime = $this->app->isDebug ? filemtime($tfile) : $pathinfo['mtime'];
        
        // 如果开启模板缓存 并且 模板存在且没有更改
        $compilePath = $this->createCompileFilePath($tfile);
        if (((extension_loaded('opcache') && opcache_is_script_cached($compilePath)) || file_exists($compilePath)) && (filemtime($compilePath) > $tfilemtime)) {
           return $compilePath;
        }
        
        // 读取模板文件
        $fh = fopen($tfile, 'rb');
        if (!$fh) {
            throw new ViewException("viewer error: fopen $tfile is faild");
        }
        
        flock($fh, LOCK_SH);
        $fsize = filesize($tfile);
        $templateContent =  ($fsize > 0) ? fread($fh, $fsize) : '';
        flock($fh, LOCK_UN);
        fclose($fh);
        
        // 解析模板并写入编译文件
        $compileContent = $this->parseTemplate($templateContent);
        $ret = file_put_contents($compilePath, $compileContent, LOCK_EX);
        if (false === $ret || !is_file($compilePath)) {
            throw new ViewException(sprintf("viewer compile error: file_put_contents %s is faild", $compilePath));
        }
        return $compilePath;
    }
    
    public function fetch($tpath, array $assign = [], $templateId = null)
    {
        $this->templateTagContents = [];
        $content = parent::fetch($tpath, $assign, $templateId);
        $this->fetchedTemplateTagContent($content);
        return $content;
    }
    
    /**
     * 生成一个编译模板文件的文件名
     *
     * @param string $tfile 输入的编译模板路径
     * @return string
     */
    protected function createCompileFilePath($tfile)
    {
        return $this->compileDir . md5($tfile) . '.template.php';
    }
    
    /**
     * 解析模板文件
     *
     * @param string $template 待解析的模板字符串
     * @return string
     *
     */
    protected function parseTemplate($template)
    {
        if (!$template) {
            return '';
        }
        
        // 调用解析前事件
        $template = $this->onPreParse($template);
        
        // 去除注释标签
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        
        // 解析变量和常量
        $template = $this->parseVariable($template);
        
        // 解析标签
        $template = $this->parseTag($template);
        
        // 替换<?php标识
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);
        // 增加template模板标识 避免include访问
        $template = "<? if(!defined('TINY_IS_IN_VIEW_ENGINE_TEMPLATE')) exit('Access Denied');?>\r\n" . $template;
        
        $template = $this->onPostParse($template);
        
        // 清理解析的template占位符列表
        return $template;
    }
    
    /**
     * 解析变量标签
     *
     * @param string $template
     * @return string
     */
    protected function parseVariable($template)
    {
        $patterns = [
            "/\{(" . self::REGEXP_VARIABLE . ")\}/", // {}包裹的变量
            "/(?<!\<\?\=|\\\\)(" . self::REGEXP_VARIABLE . ")/" // 没有{}包裹的变量
        ];
        
        // 变量
        $template = preg_replace($patterns, "<?=\\1?>", $template);
        
        // 常量
        $template = preg_replace_callback("/" . self::REGEXP_CONST . "/", [
            $this,
            'parseConstVariable'
        ], $template); // {}包裹的常量
        return $template;
    }
    
    /**
     * 解析常量为已预设常量的字符串
     *
     * @param array $matchs 正则匹配数组
     * @return string|mixed
     */
    protected function parseConstVariable($matchs)
    {
        $constName = $matchs[1];
        if (!defined($constName)) {
            return $matchs[0];
        }
        return constant($constName);
    }
    
    /**
     * 解析tag
     *
     * @param string $template 模板字符串
     * @return string 返回解析tag后的模板字符串
     */
    protected function parseTag($template)
    {
        $pattents = [
            "/\{([a-z]+(?:[\.\-_][a-z0-9]+)?)(?:\s+(.*?))?(?:(?:\|([^|]*?))?)?\}/is",
            "/\{\/([a-z]+(?:[\.\-_][a-z0-9]+)?)\}/is",
            "/\{(else)\}/"
        ];
        
        // 处理
        $template = preg_replace_callback($pattents, [
            $this,
            'parseMatchedTag'
        ], $template);
        return $template;
    }
    
    /**
     * 解析匹配成功的标签
     *
     * @param array $matchs
     * @return boolean|string
     */
    protected function parseMatchedTag($matchs)
    {
        
        $tagFullText = $matchs[0];
        $tagName = $matchs[1];
        
        // 闭合标签处理
        if ($tagFullText[1] == '/') {
            $ret = $this->onParseCloseTag($tagName);
        } else {
            // 非闭合标签
            $tagBody = $this->stripVariableTag($matchs[2]);
            $extra = $matchs[3];
            $ret = $this->onParseTag($tagName, $tagBody, $extra);
        }
        
        // 非false则返回
        if ($ret !== false) {
            return $ret;
        }
        return $tagFullText;
    }
    
    /**
     * 替换tag内的变量标签
     *
     * @param string $match 匹配字符串
     * @return string
     */
    protected function stripVariableTag($tagBody)
    {
        if (!$tagBody) {
            return '';
        }
        
        // @formatter:off
        $patterns = [ '/' . self::REGEXP_VARIABLE_TAG . '/is', '/\\"/', '/\s+/'];
        $replaces = ['\\1', '"', ' '];
        
        // @formatter:on
        return preg_replace($patterns, $replaces, $tagBody);
    }
    
    /**
     * 解析前发生
     *
     * @param string $template 解析前的模板字符串
     * @return false|string
     */
    protected function onPreParse($template)
    {
        foreach ($this->plugins as $pname) {
            if (!key_exists($pname, $this->pluginInstances)) {
                $this->pluginInstances[$pname] = $this->app->get($pname);
            }
            $instance = $this->pluginInstances[$pname];
            $ret = $instance->onPreParse($template);
            if (false !== $ret) {
                return $ret;
            }
        }
        return $template;
    }
    
    /**
     * 解析闭合标签
     *
     * @param string $tagName
     */
    protected function onParseCloseTag($tagName)
    {
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
        return $this->onPluginParseCloseTag($tagName);
    }
    
    /**
     * 解析tag
     *
     * @param string $tagName 标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加标识
     * @return string|boolean 返回解析成功的字符串 false为解析失败
     */
    protected function onParseTag($tagName, $tagBody, $extra)
    {
        switch ($tagName) {
            case 'loop':
                return $this->parseLoopsection($tagBody, $extra);
            case 'foreach':
                return $this->parseLoopsection($tagBody, $extra);
            case 'for':
                return $this->parseForTag($tagBody, $extra);
            case 'if':
                return $this->parseIfTag($tagBody, $extra);
            case 'else':
                return $this->parseElseTag($tagBody, $extra);
            case 'elseif':
                return $this->parseElseIfTag($tagBody, $extra);
            case 'eval':
                return $this->parseEvalTag($tagBody, $extra);
        }
        return $this->onPluginParseTag($tagName, $tagBody, $extra);
    }
    
    /**
     * 解析完成后发生
     *
     * @param string $template 解析后的模板字符串
     * @return false|string
     */
    protected function onPostParse($template)
    {
        // 解析template tag占位符
        $this->onPostParseTemplateTag($template);
        
        // plugins
        foreach ($this->plugins as $pname) {
            if (!key_exists($pname, $this->pluginInstances)) {
                $this->pluginInstances[$pname] = $this->app->get($pname);
            }
            $instance = $this->pluginInstances[$pname];
            $ret = $instance->onPostParse($template);
            if (false !== $ret) {
                return $ret;
            }
        }
        
        return $template;
    }
    
    /**
     * 调用插件事件解析闭合tag
     *
     * @param string $tagName 标签名
     * @param string $tagBody 标签主体内容
     * @param boolean $isCloseTag 是否闭合标签
     * @return string|boolean 返回解析成功的字符串 false时没有找到解析成功的插件 或者解析失败
     */
    protected function onPluginParseCloseTag($tagName)
    {
        foreach ($this->plugins as $pname) {
            if (!key_exists($pname, $this->pluginInstances)) {
                $this->pluginInstances[$pname] = $this->app->get($pname);
            }
            $instance = $this->pluginInstances[$pname];
            $ret = $instance->onParseCloseTag($tagName);
            if (false !== $ret) {
                return $ret;
            }
        }
        return false;
    }
    
    /**
     * 调用插件事件解析tag
     *
     * @param string $tagName 标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加信息
     * @return string|boolean 返回解析成功的字符串 false时没有找到解析成功的插件 或者解析失败
     */
    protected function onPluginParseTag($tagName, $tagBody, $extra)
    {
        $regex = '/(' . self::REGEXP_VARIABLE . ')/';
        $tagBody = preg_replace($regex, '{\\1}', $tagBody);
        $extra = preg_replace($regex, '{\\1}', $extra);
        switch ($tagName) {
            case 'template':
                return $this->parseTemplateTag($tagBody, $extra);
            case 'date':
                return $this->parseDateTag($tagBody, $extra);
            case 'url':
                return $this->parseUrlTag($tagBody, $extra);
        }
        
        // plugins
        foreach ($this->plugins as $pname) {
            if (!key_exists($pname, $this->pluginInstances)) {
                $this->pluginInstances[$pname] = $this->app->get($pname);
            }
            $instance = $this->pluginInstances[$pname];
            $ret = $instance->onParseTag($tagName, $tagBody, $extra);
            if (false !== $ret) {
                return $ret;
            }
        }
        return false;
    }
    
    /**
     * 解析遍历数组循环
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|false;
     */
    protected function parseLoopsection($tagBody, $extra)
    {
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
    protected function parseForTag($tagBody, $extra = null)
    {
        return sprintf('<? for ( %s ) { ?>', $tagBody);
    }
    
    /**
     * 解析Else标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 标签标识
     * @return string
     *
     */
    protected function parseElseTag($tagBody, $extra = null)
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
    protected function parseIfTag($tagBody, $extra = null)
    {
        return sprintf('<?  if( %s ) { ?>', $tagBody);
    }
    
    /**
     * 解析elseif标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|false;
     */
    protected function parseElseIfTag($tagBody, $extra = null)
    {
        return sprintf('<? } elseif( %s ) { ?>', $tagBody);
    }
    
    /**
     * 解析eval标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|false;
     */
    protected function parseEvalTag($tagBody, $extra = null)
    {
        return sprintf('<? %s ?>', $tagBody);
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
    protected function parseTemplateTag($tagBody, $extra = null)
    {
        $content = '';
        $tfile = $tagBody;
        $inject = '';
        $templateId = $extra;
        $name = '';
        
        // 匹配输出
        if ($attrs = $this->parseAttr($tagBody)) {
            $tfile = (string)$attrs['file'];
            $name = (string)$attrs['name'];
            $templateId = (string)$attrs['id'] ?: $extra;
            if ($attrs['inject'] && $attrs['inject'] != 'false') {
                $inject = ',true';
            }
        }
        
        if (!$name && !$tfile) {
            return '';
        }
        if ($tfile) {
            $viewEngine = $this->view->getEngineByPath($tfile);
            if (!$viewEngine) {
                return '';
            }
        }
        if (!$templateId && $this->fetchingTemplateId) {
            $templateId = $this->fetchingTemplateId;
        }
        
        if (!$name) {
            return ($viewEngine instanceof Template)
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
            $isSelf = $viewEngine instanceof Template ? 'true' : 'false';
            $content .= sprintf('<? echo $this->fetchingTemplateTag("%s", $this->fetchingVariables ,"%s", "%s", %s);?>', $tfile, $templateId, $name,  $isSelf);
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
     * 解析模板标签
     *
     * @param string $tfile 模板
     * @param string $templateId
     * @param string $name
     * @param string $inject
     * @return string
     */
    protected function fetchingTemplateTag($tpath, $assigns, $templateId = null, $name = '', $inject = false)
    {
        // content
        $content = '';
        if ($tpath) {
            $viewEngine = $this->view->getEngineByPath($tpath);
            if ($viewEngine instanceof Template) {
                $compileFile = $this->getCompiledFile($tpath);
                $content = $this->fetchCompiledContent($compileFile);
            } else {
                $content = $this->view->fetch($tpath, $assigns, $templateId);
            }
        }
        
        if (!$name) {
            return $content;
        }
        
        // merge
        if (!key_exists($name, $this->templateTagContents)) {
            $this->templateTagContents[$name] = '';
        }
        
        $this->templateTagContents[$name] .= $content;
        return '';
    }
    
    /**
     * 全局替换占位符
     *
     * @param string $content
     */
    public function fetchedTemplateTagContent(&$content)
    {
        while (preg_match('/<\!\-\-template\.content\.\w+\-\->/is', $content)) {
            $content = preg_replace_callback("/<\!\-\-template\.content\.(\w+)\-\->/is", [
                $this,
                'onFetchedTemplateTagContent'
            ], $content);
        }
    }
    
    /**
     * 合并输出内容
     *
     * @param array $matchs
     * @return string
     */
    protected function onFetchedTemplateTagContent($matchs)
    {
        $name = $matchs[1];
        if (!key_exists($name, $this->templateTagContents)) {
            return '';
        }
        
        // 输出
        $content = $this->templateTagContents[$name];
        unset($this->templateTagContents[$name]);
        return $content;
    }
    
    /**
     * 解析date标签
     *
     * @param string $tagBody 解析的标签设置
     * @param string $extra 附加信息
     * @return string
     */
    protected function parseDateTag($tagBody, $extra = null)
    {
        $time = trim($tagBody);
        if (!preg_match("/^\d+$/", $time)) {
            $time = strtotime($time) ?: time();
        }
        $format = trim($extra) ?: 'Y-m-d H:i:s';
        return sprintf('<? echo date("%s", %d);?>', $format, $time);
    }
    
    /**
     * 解析URL标签
     *
     * @param string $tagBody 解析的标签主体信息
     * @param string $extra 附加信息
     * @return string
     */
    protected function parseUrlTag($tagBody, $extra = null)
    {
        $paramText = explode(',', $tagBody);
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