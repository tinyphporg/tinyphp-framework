<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ParserManager.php
 * @author King
 * @version stable 2.0
 * @Date 2022年12月6日下午3:59:51
 * @Class List class
 * @Function List function_container
 * @History King 2022年12月6日下午3:59:51 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Engine\Tagger;

use Tiny\MVC\View\ViewManager;
use Tiny\MVC\View\Engine\Tagger\Parser\SyntaxParser;
use Tiny\MVC\View\Helper\WidgetHelper;

/**
 * 解析器 管理器
 */
class ParserManager
{
    
    /**
     * 默认加载的解析器
     *
     * @var array
     */
    const DEFAULT_PARSER_LIST = [
        SyntaxParser::class // 基本语法解析器
    ];
    
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
    const REGEXP_CONST = "\{([\w]+)\}";
    
    /**
     * 视图管理器
     *
     * @var ViewManager
     */
    protected $viewManager;
    
    /**
     * 解析器配置集合
     *
     * @var array
     */
    protected $parsers = [];
    
    /**
     *
     * @param ViewManager $viewManager
     * @param array $config
     */
    public function __construct(ViewManager $viewManager, array $config = [])
    {
        $this->viewManager = $viewManager;
        
        foreach (self::DEFAULT_PARSER_LIST as $parserClass) {
            $this->parsers[$parserClass] = $this->createParser($parserClass);
        }
        
        // 解析配置数组
        foreach ($config as $parserItem) {
            $parserClass = (string)$parserItem['parser'];
            if (!$parserClass) {
                continue;
            }
            $parserConfig = (array)$parserItem['config'];
            $this->parsers[$parserClass] = $this->createParser($parserClass, $parserConfig);
        }
        
        // 加载视图小部件作为解析器
        $widgetHelper = $this->viewManager->getWidgetHelper();
        if ($widgetHelper) {
            $this->parsers[WidgetHelper::class] = $widgetHelper;
        }
    }
    
    /**
     * 创建指定解析器的实例
     *
     * @param string $parserClass 解析器类名
     * @param array $config 配置数组
     * @return \Tiny\MVC\View\Engine\Tagger\Parser\ParserInterface
     */
    protected function createParser($parserClass, array $config = [])
    {
        $parserInstance = $this->viewManager->getOrCreateInstance($parserClass, [
            'config' => $config
        ]);
        return $parserInstance;
    }
    
    /**
     * 解析模板文件
     *
     * @param string $template 待解析的模板字符串
     * @return string
     *
     */
    public function parseTemplate($template)
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
        $template = preg_replace("/" . self::REGEXP_CONST . "/", "<?=  defined(\"\\1\") ? constant(\"\\1\") : \"\\1\"; ?>", $template); // {}包裹的常量
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
            "/{([a-z][a-z\-]*\:)?([a-z]+(?:\-[a-z0-9]+)*)(?:\s+(.*?)\s*)?(?<![\?\\\\\-])(\/)?\}/is",
            "/\{(\/)([a-z][a-z\-]*\:)?([a-z]+(?:\-[a-z0-9]+)*)(?<![\?\\\\\-])\}/is",
            "/\<([a-z][a-z\-]*)?\:([a-z]+(?:\-[a-z0-9]+)*)(?:\s+(.*?)\s*)?(?<![\?\\\\\-])(\/)?\>/is",
            "/\<(\/)([a-z][a-z\-]*)?\:([a-z]+(?:\-[a-z0-9]+)*)(?<![\?\\\\\-])\>/is",
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
        
        // 闭合标签
        if ($matchs[1] == '/') {
            $nameSpace = $matchs[2];
            $tagName = $matchs[3];
            $ret = $this->onParseCloseTag($tagName, $nameSpace);
            return $ret !== false ? $ret : $tagFullText;
        }
        
        // 标签
        $nameSpace = $matchs[1];
        $tagName = $matchs[2];
        $params = $this->parseTagParams($matchs[3]);
        $ret = $this->onParseTag($tagName, $nameSpace, $params);
        return $ret !== false ? $ret : $tagFullText;
    }
    
    /**
     * 解析成属性对
     *
     * @param string $tagBody 标签主体内容
     */
    protected function parseTagParams($tagBody)
    {
        $tagBody = trim($tagBody);
        if (!$tagBody) {
            return [];
        }
        $tagBody = $this->stripVariableTag($tagBody);
        $fullText = preg_replace("/{(" . self::REGEXP_VARIABLE . ")}/", "\\1", $tagBody);
        $params = [
            $fullText
        ];
        
        $tagBody = preg_replace_callback("/([a-z][a-z\-]*?)\s*=\s*('.+?'|\".+?\"|[^'\"]+?)(?:\s+|$)/i", function ($matchs) use (&$params) {
            $name = $matchs[1];
            $value = trim($matchs[2], '"\'');
            $params[$name] = $value;
            return '';
        }, $tagBody);
        
        $num = 0;
        $nodes = explode(' ', $tagBody);
        foreach ($nodes as $node) {
            $node = trim($node);
            if (!$node) {
                continue;
            }
            $num++;
            $params[$num] = $node;
        }
        return $params;
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
        $patterns = [ '/' . self::REGEXP_VARIABLE_TAG . '/is', '/\\"/', '/\\\>/', '/\s+/'];
        $replaces = ['{\\1}', '"', '>' , ' '];
        
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
        foreach ($this->parsers as $parser) {
            $ret = $parser->onPreParse($template);
            if (false !== $ret) {
                $template = $ret;
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
        foreach ($this->parsers as $parser) {
            $ret = $parser->onParseCloseTag($tagName);
            if (false !== $ret) {
                return $ret;
            }
        }
        return false;
    }
    
    /**
     * 解析tag
     *
     * @param string $tagName 标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加标识
     * @return string|boolean 返回解析成功的字符串 false为解析失败
     */
    protected function onParseTag($nameSpace, $tagName, array $params = [])
    {
        foreach ($this->parsers as $parser) {
            
            $ret = $parser->onParseTag($nameSpace, $tagName, $params);
            if (false !== $ret) {
                return $ret;
            }
        }
        return false;
    }
    
    /**
     * 解析完成后发生
     *
     * @param string $template 解析后的模板字符串
     * @return false|string
     */
    protected function onPostParse($template)
    {
        // 解析器
        foreach ($this->parsers as $parser) {
            $ret = $parser->onPostParse($template);
            if (false !== $ret) {
                $template = $ret;
            }
        }
        return $template;
    }
}
?>