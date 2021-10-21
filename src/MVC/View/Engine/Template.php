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
 *           King 2020年6月1日14:21 stable 1.0.01 审定
 *
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\Helper\Url;

use Tiny\MVC\View\Engine\Template\IPlugin;
use Tiny\MVC\View\Engine\Base;
use Tiny\MVC\View\ViewException;
use Tiny\MVC\View\View;

define('IN_TINYPHP_VIEW_TEMPLATE', TRUE);
/**
 * 简单的解析引擎
 *
 * @package Tiny\MVC\View\Engine
 * @since 2013-5-25上午08:21:38
 * @final 2013-5-25上午08:21:38
 */
class Template extends Base
{
    
    /**
     * 匹配变量的正则
     * 
     * @var string
     */
    const REGEXP_VARIABLE = "\@?\\\$[a-zA-Z_]\w*(?:(?:\-\>[a-zA-Z_]\w*)?(?:\[[\w\.\"\'\[\]\$]+\])?)*";
    
    /**
     * 匹配标签里变量标识的正则
     * @var string
     */
    const REGEXP_VARIABLE_TAG =  "\<\?=(\@?\\\$[a-zA-Z_]\w*(?:(?:\-\>[a-zA-Z_]\w*)?(?:\[[\w\.\"\'\[\]\$]+\])?)*)\?\>";
    
    /**
     * 匹配常量的正则
     * 
     * @var string
     */
    const REGEXP_CONST = "\{((?!else)[\w]+)\}";
    
    /**
     * 注册的模板插件实例
     * 
     * @var array
     */
    protected $_plugins = [];
    /**
     * 注册函数
     * @param IPlugin $plugin
     */
    public function regPlugin(IPlugin $plugin)
    {
        if (!in_array($plugin))
        {
            $this->_plugins = $plugin;
        }
    }
    

    
    public function _onCloseTagMatch($match)
    {
        
    }
    
    
    /**
     * 获取输出的html内容
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\View\Engine\IEngine::fetch()
     */
    public function fetch($tpath, $isAbsolute = FALSE)
    {
        $compileFile = $this->_getCompileFile($tpath, $isAbsolute);
        
        ob_start();
        extract($this->_variables, EXTR_SKIP);
        include $compileFile;
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
        return $content;
    }
    
    
    /**
     * 获取template真实路径
     * @param string $tpath
     * @param boolean $isAbsolute
     * @return mixed
     */
    protected function _getTemplateRealPath($tpath, $isAbsolute = FALSE)
    {
        if ($isAbsolute && is_file($tpath))
        {
            return $tpath;
        }
        
        if ($isAbsolute)
        {
            return FALSE;
        }
        
        if (is_array($this->_templateDir))
        {
            foreach($this->_templateDir as $tdir)
            {
                $tePath = $tdir . $tpath;
                if (is_file($tePath))
                {
                    return $tePath;
                }
            }
            return FALSE;
        }
        
        $tpath = $this->_templateDir . $tpath;
        if (!is_file($tpath))
        {
            return FALSE;
        }
        return $tpath;
    }
    /**
     * 获取模板解析后的文件路径
     *
     * @param string $file
     *        文件路径
     * @param bool $isAbsolute
     *        是否绝对位置
     * @return string $path
     */
    protected function _getCompileFile($tpath, $isAbsolute = FALSE)
    {
        $tfile = $this->_getTemplateRealPath($tpath, $isAbsolute);
        if (!$tfile)
        {
            throw new ViewException(sprintf("viewer error: the template file %s is not exists!", $tpath));
        }
        
        $compileFileName = md5($tfile) . '.template.php';
        $compilePath = $this->_compileDir . $compileFileName;
        
        // 如果开启模板缓存 并且 模板存在且没有更改
        if (false || $this->_cacheEnabled && file_exists($compilePath) && filemtime($compilePath) > filemtime($tfile))
        {
            return $compilePath;
        }
        
        // 读取模板文件
        $fh = fopen($tfile, 'rb');
        if (!$fh)
        {
            throw new ViewException("viewer error: fopen $tfile is faild");
        }
        flock($fh, LOCK_SH);
        $templateContent = fread($fh, filesize($tfile));
        flock($fh, LOCK_UN);
        fclose($fh);
        
        // 解析模板并写入编译文件
        $compileContent = $this->_parseTemplate($templateContent);
        $ret = file_put_contents($compilePath, $compileContent, LOCK_EX);
        if (!$ret|| !is_file($compilePath))
        {
            throw new ViewException(sprintf("viewer compile error: file_put_contents %s is faild", $compilePath));
        }
        
        return $compilePath;
    }

    /**
     * 解析模板文件
     *
     * @param string $template
     *        待解析的模板字符串
     * @return string
     *
     */
    protected function _parseTemplate($template)
    {
        //去除注释标签
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        
        // 解析变量和常量
        $template = $this->_parseVariable($template);
        
        //解析标签
        $template = $this->_parseTag($template);
        
        echo $template;
        return $template; 
         
        $template = preg_replace_callback("/\{elseif\s+(.+?)\}/is", [
            $this,
            "_stripElseIfTag"
        ], $template);
        $template = preg_replace_callback("/\{date\s+(.+?)\}/is", [
            $this,
            "_date"
        ], $template);

        $template = preg_replace_callback("/\{if\s+(.+?)\}/is", [
            $this,
            "_stripIfTag"
        ], $template);
        $template = preg_replace("/\{template\s+(\w+?)\}/is", "<? include \$this->_getCompilePath('\\1');?>", $template);
        $template = preg_replace_callback("/\{template\s+(.+?)\}/is", [
            $this,
            "_stripvIncludeTag"
        ], $template);
        $template = preg_replace("/\{else\}/is", "<? } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/is", "<? } ?>", $template);
        $template = preg_replace("/\{\/for\}/is", "<? } ?>", $template);
        $template = preg_replace("/$this->const_regexp/", "<?=\\1?>", $template);

        $template = "<? if(!defined('IN_TINYPHP_VIEW_TEMPLATE')) exit('Access Denied');?>\r\n$template";
        $template = preg_replace("/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i", "\\1'\\2']", $template);
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);

        return $template;
    }
    
    /**
     * 解析变量标签
     * 
     * @param string $template
     * @return string
     */
    protected function _parseVariable($template)
    {
        $patterns = [
            "/\{(" . self::REGEXP_VARIABLE . ")\}/",  // {}包裹的变量
            "/" . self::REGEXP_CONST . "/",  //{}包裹的常量
            "/(?<!\<\?\=|\\\\)(" . self::REGEXP_VARIABLE . ")/" //没有{}包裹的变量
        ];
        
        return preg_replace($patterns, "<?=\\1?>", $template);
    }
    
    /**
     * 解析标签
     * @param string $template
     * @return string
     */
    protected function _parseTag($template) 
    {
        $pattents = [
            "/\{([a-z]+)\s+(.*?)\}/is",
            "/\{(\/)([a-z]+)\}/is",
            "/\{(else)\}/"
        ];
        $template = preg_replace_callback($pattents, [
            $this,
            "_parseMatchingTag"
        ], $template);
        return $template;
    }
    
    /**
     * 解析匹配成功的标签
     * 
     * @param array $matchs
     * @return boolean|string
     */
    protected function _parseMatchingTag($matchs)
    {
        $isCloseTag = ($matchs[1] == '/') ? TRUE : FALSE;
        $tagName = $isCloseTag ? $matchs[2] : $matchs[1];
        $tagBody = $isCloseTag ?  NULL : $matchs[2];
        if ($tagBody) 
        {
            $tagBody = $this->_stripVariableTag($tagBody);
        }
        $tag = $this->onParseTag($tagName, $tagBody, $isCloseTag);
        if ($tag !== FALSE)
        {
            return $tag;
        }
       return $matchs[0];
    }
    
    /**
     * 变量标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     */
    protected function _stripVariableTag($tagBody)
    {
        $patterns = ["/" .self::REGEXP_VARIABLE_TAG . "/is", "/\\\"/", "/\s+/"];
        $replaces = ["\\1", '"', " "];
        return preg_replace($patterns, $replaces, $tagBody);
    }
    
    
    public function onParseTag($tagName,  $tagBody, $isCloseTag = FALSE)
    {
        switch($tagName) 
        {
            case 'loop':
                return $this->_parseLoopsection($tagBody, $isCloseTag);
            case 'foreach':
                return $this->_parseLoopsection($tagBody, $isCloseTag);
            case 'for':
                return $this->_parseForTag($tagBody, $isCloseTag);
            case 'if':
                return $this->_parseIfTag($tagBody, $isCloseTag);
            case 'else':
                return $this->_parseElseTag($tagBody, $isCloseTag);
            case 'elseif':
                return $this->_parseElseIfTag($tagBody, $isCloseTag);
            case 'eval':
                return $this->_parseEvalTag($tagBody, $isCloseTag);
            case 'template':
                return $this->_parseTemplateTag($tagBody, $isCloseTag);
        }
       // return $tagBody;
        return FALSE;
    }
    
    /**
     * 解析遍历数组循环
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _parseLoopsection($tagBody, $isCloseTag)
    {
       if ($isCloseTag)
       {
           return '<? } ?>';
       }
       $tagNodes = explode(" ", $tagBody);
       $nodeNum = count($tagNodes);
       if (2 == $nodeNum || 3 == $nodeNum)
       {
           return (3 == $nodeNum) 
           ? sprintf("<? foreach((array)%s as %s => %s) { ?>", $tagNodes[0], $tagNodes[1], $tagNodes[2])
           : sprintf("<? foreach((array)%s as %s) { ?>", $tagNodes[0], $tagNodes[1]);
       }
       return FALSE; 
    }
    
    /**
     * 过滤标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _parseForTag($tagBody, $isCloseTag)
    {
        if ($isCloseTag)
        {
            return '<? } ?>';
        }
        return sprintf('<? for ( %s ) { ?>', $tagBody);
    }
    
    /**
     * 过滤标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _parseElseTag($tagBody, $isCloseTag)
    {
        return $isCloseTag ? '' : '<? } else { ?>';
    }

    /**
     * 过滤标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _parseIfTag($tagBody, $isCloseTag)
    {
        if ($isCloseTag)
        {
            return '<? } ?>';
        }
        return sprintf('<?  if( %s ) { ?>', $tagBody);
    }
    
    /**
     * 过滤标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _parseElseIfTag($tagBody, $isCloseTag)
    {
        if ($isCloseTag)
        {
            return '';
        }
        return sprintf('<? } elseif( %s ) { ?>', $tagBody);
    }
    
    /**
     * 过滤标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _parseEvalTag($tagBody, $isCloseTag)
    {
        if ($isCloseTag)
        {
            return '';
        }
        return sprintf('<? %s ?>', $tagBody);
    }
    


    /**
     * include标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _parseTemplateTag($tagBody, $isCloseTag)
    {
        if ($isCloseTag)
        {
            return '';
        }
        $engineInstance = View::getInstance()->getEngineByPath($tagBody);
        if ($engineInstance == $this)
        {
            return sprintf('<? include $this->_getCompileFile("%s"); ?>', $tagBody);
        }
        else
        {
            return sprintf('<? $view->fetch("%s"); ?>', $tagBody);
        }
    }

    /**
     * 解析脚本标签
     *
     * @param string $match
     *        标识符
     * @return string
     *
     */
    protected function _stripvtag($match)
    {
        $s = $match[1];
        return $this->_doStripvtag($s);
    }



    /**
     * 解析时间标签
     *
     * @param string $match
     *        匹配字符串
     *        字符串
     * @return void
     */
    protected function _date($match)
    {
        $s = $match[1];
        $d = explode('|', $s);
        if (!$d[1])
        {
            $d[1] = 'y-m-d H:i';
        }
        $fromat = $d[1];
        $v = trim($this->_doStripvtag($d[0]));
        return "<? echo date(\"$fromat\", $v)?>";
    }



    /**
     * 解析URL模板
     *
     * @param string $match
     *        品牌字符串
     * @param string $type
     *        模板类型
     * @return string
     */
    protected function _resolvUrl($match)
    {
        $param = $match[2];
        $type = $match[3];
        $params = explode(',', $param);
        $ps = [];
        if (is_array($params))
        {
            foreach ($params as $v)
            {
                $vs = explode('=', $v);
                $ps[$vs[0]] = $vs[1];
            }
        }
        return Url::get($ps, $type);
    }
}
?>