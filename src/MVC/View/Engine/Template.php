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

use Tiny\MVC\View\Engine\Template\IPlugin;
use Tiny\MVC\View\ViewException;
use Tiny\MVC\View\View;
define('IN_TINYPHP_VIEW_ENGINE_TEMPLATE', TRUE);

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
     *
     * @var string
     */
    const REGEXP_VARIABLE_TAG = "\<\?=(\@?\\\$[a-zA-Z_]\w*(?:(?:\-\>[a-zA-Z_]\w*)?(?:\[[\w\.\"\'\[\]\$]+\])?)*)\?\>";

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
     *
     * @param IPlugin $plugin
     */
    public function regPlugin($pconfig)
    {
        if (!is_array($pconfig))
        {
            return FALSE;
        }
        
        if (!key_exists('plugin', $pconfig) || !is_string($pconfig['plugin']))
        {
            return FALSE;
        }
        
        $pluginName  = (string)$pconfig['plugin'];
        $config = (array)$pconfig['config'];
        if (!key_exists($pluginName, $this->_plugins))
        {
            $this->_plugins[$pluginName] = [
                'plugin' => $pluginName,
                'config' => $config,
                'instance' => NULL,
            ];
            return TRUE;
        }
        $plugin = & $this->_plugins[$pluginName];
        $plugin['config'] += $config;
        if(!isset($plugin['plugin']))
        {
            $plugin['plugin'] = $pluginName;
        }
        return TRUE;
    }

    /**
     * 设置template解析插件
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Base::setEngineConfig()
     */
    public function setEngineConfig(View $view, array $config)
    {
        parent::setEngineConfig($view, $config);
        foreach($config['plugins'] as $pconfig)
        {
            $this->regPlugin($pconfig);  
        }
    }

    /**
     * 获取模板解析后的文件路径
     *
     * @param string $file
     *            文件路径
     * @param bool $isAbsolute
     *            是否绝对位置
     * @return string $path
     */
    public function getCompiledFile($tpath, $isAbsolute = FALSE)
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
        if (!$ret || !is_file($compilePath))
        {
            throw new ViewException(sprintf("viewer compile error: file_put_contents %s is faild", $compilePath));
        }
        return $compilePath;
    }

    /**
     * 解析模板文件
     *
     * @param string $template
     *            待解析的模板字符串
     * @return string
     *
     */
    protected function _parseTemplate($template)
    {
        // 去除注释标签
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);

        // 解析变量和常量
        $template = $this->_parseVariable($template);

        // 解析标签
        $template = $this->_parseTag($template);
        
        //替换<?php标识
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);
        
        //增加template模板标识 避免include访问
        $template = "<? if(!defined('IN_TINYPHP_VIEW_ENGINE_TEMPLATE')) exit('Access Denied');?>\r\n" . $template;
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
            "/\{(" . self::REGEXP_VARIABLE . ")\}/", // {}包裹的变量
            "/" . self::REGEXP_CONST . "/", // {}包裹的常量
            "/(?<!\<\?\=|\\\\)(" . self::REGEXP_VARIABLE . ")/" // 没有{}包裹的变量
        ];

        return preg_replace($patterns, "<?=\\1?>", $template);
    }

    /**
     * 解析标签
     *
     * @param string $template
     * @return string
     */
    protected function _parseTag($template)
    {
        $pattents = [
            "/\{([a-z]+)\s+(.*?)(?:\|([a-z][a-z0-9_]*?))?\}/is",
            "/\{\/([a-z]+)\}/is",
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
        $fullTagText = $matchs[0];
        $isCloseTag = ($fullTagText[1] == '/') ? TRUE : FALSE;
        $tagName = $matchs[1];
        if ($isCloseTag)
        {
            $ret = $this->onParseCloseTag($tagName);
        }
        else
        {
            $tagBody = $this->_stripVariableTag($matchs[2]);
            $extra = $matchs[3];
            $ret = $this->onParseTag($tagName, $tagBody, $extra);
        }
        if ($ret !== FALSE)
        {
            return $ret;
        }
        return $fullTagText;
    }

    /**
     * 变量标签
     *
     * @param string $match
     *            匹配字符串
     * @return string
     */
    protected function _stripVariableTag($tagBody)
    {
        if(!$tagBody)
        {
            return '';
        }
        $patterns = [
            "/" . self::REGEXP_VARIABLE_TAG . "/is",
            "/\\\"/",
            "/\s+/"
        ];
        $replaces = [
            "\\1",
            '"',
            " "
        ];
        return preg_replace($patterns, $replaces, $tagBody);
    }

    /**
     * 解析闭合标签
     * 
     * @param string $tagName
     */
    public function onParseCloseTag($tagName)
    {
        switch ($tagName)
        {
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
            case 'date':
            default:
                return FALSE;
        }
        return $this->_onPluginParseCloseTag($tagName);
    }
    
    /**
     * 解析tag
     *
     * @param string $tagName  标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加标识
     * @return string|boolean 返回解析成功的字符串  FALSE为解析失败
     */
    public function onParseTag($tagName, $tagBody, $extra)
    {
        switch ($tagName)
        {
            case 'loop':
                return $this->_parseLoopsection($tagBody, $extra);
            case 'foreach':
                return $this->_parseLoopsection($tagBody, $extra);
            case 'for':
                return $this->_parseForTag($tagBody, $extra);
            case 'if':
                return $this->_parseIfTag($tagBody, $extra);
            case 'else':
                return $this->_parseElseTag($tagBody, $extra);
            case 'elseif':
                return $this->_parseElseIfTag($tagBody, $extra);
            case 'eval':
                return $this->_parseEvalTag($tagBody, $extra);
            case 'template':
                return $this->_parseTemplateTag($tagBody, $extra);
            case 'date':
                return $this->_parseDateTag($tagBody, $extra);
        }
        return $this->_onPluginParseTag($tagName, $tagBody, $extra);
    }
    
    /**
     * 调用插件事件解析闭合tag
     *
     * @param string $tagName  标签名
     * @param string $tagBody 标签主体内容
     * @param boolean $isCloseTag 是否闭合标签
     * @return string|boolean 返回解析成功的字符串  FALSE时没有找到解析成功的插件 或者解析失败
     */
    protected function _onPluginParseCloseTag($tagName)
    {
        foreach($this->_plugins as $pconfig)
        {
            $instance = $pconfig['instance'];
            if(!$instance)
            {
                $instance = $this->_getPluginInstanceByConfig($pconfig);
            }
            $ret = $instance->onParseCloseTag($tagName);
            if(FALSE !== $ret)
            {
                return $ret;
            }
        }
        return FALSE;
    }
    
    /**
     * 调用插件事件解析tag
     * 
     * @param string $tagName  标签名
     * @param string $tagBody 标签主体内容
     * @param string $extra 附加信息
     * @return string|boolean 返回解析成功的字符串  FALSE时没有找到解析成功的插件 或者解析失败
     */
    protected function _onPluginParseTag($tagName, $tagBody, $extra)
    {
        foreach($this->_plugins as $pconfig)
        {
            $instance = $pconfig['instance'];
            if(!$instance)
            {
                $instance = $this->_getPluginInstanceByConfig($pconfig);
            }
            $ret = $instance->onParseTag($tagName, $tagBody, $extra);
            if(FALSE !== $ret)
            {
                return $ret;
            }
        }
        return FALSE;
    }
    
    /**
     * 根据配置返回插件实例
     * @param array $pconfig 配置实例
     * @return IPlugin 实现了Template引擎插件接口IPlugin的实例
     */
    protected function _getPluginInstanceByConfig($pconfig)
    {
        $pluginName = $pconfig['plugin'];
        if (!class_exists($pluginName))
        {
            throw new ViewException(sprintf('Template Engine: Plugin class:%s is not exists!', $pluginName));
        }
        $pluginInstance = new $pluginName();
        $pluginInstance->setTemplateConfig($this, (array)$pconfig['config']);
        $this->_plugins[$pluginName]['instance'] = $pluginInstance;
        return $pluginInstance;
    }
    
    /**
     * 解析遍历数组循环
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|FALSE;
     */
    protected function _parseLoopsection($tagBody, $extra)
    {
        $tagNodes = explode(" ", $tagBody);
        $nodeNum = count($tagNodes);
        if (2 == $nodeNum || 3 == $nodeNum)
        {
            return (3 == $nodeNum) ? sprintf("<? foreach((array)%s as %s => %s) { ?>", $tagNodes[0], $tagNodes[1], $tagNodes[2]) : sprintf("<? foreach((array)%s as %s) { ?>", $tagNodes[0], $tagNodes[1]);
        }
        return FALSE;
    }

    /**
     * 解析For标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加标识
     * @return string
     */
    protected function _parseForTag($tagBody, $extra = NULL)
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
    protected function _parseElseTag($tagBody, $extra = NULL)
    {
        return '<? } else { ?>';
    }

    /**
     * 解析if标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|FALSE;
     */
    protected function _parseIfTag($tagBody, $extra = NULL)
    {
        return sprintf('<?  if( %s ) { ?>', $tagBody);
    }

    /**
     * 解析elseif标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|FALSE;
     */
    protected function _parseElseIfTag($tagBody, $extra = NULL)
    {
        return sprintf('<? } elseif( %s ) { ?>', $tagBody);
    }

    /**
     * 解析eval标签
     *
     * @param string $tagBody 标签主体
     * @param string $extra 附加信息
     * @return string|FALSE;
     */
    protected function _parseEvalTag($tagBody, $extra = NULL)
    {
        return sprintf('<? %s ?>', $tagBody);
    }

    /**
     * 解析template标签
     * 
     *  解析出的模板路径，会通过View的单例调用对应的模板引擎实例->fetch()内容替换
     *  该模板引擎实例 是继承了Base的PHP/Template 直接替换为include运行, 可以共享变量空间。
     *
     * @param string $tagBody
     *            解析的模板路径
     * @param boolean $isCloseTag
     *            是否为闭合标签
     * @return string
     */
    protected function _parseTemplateTag($tagBody, $extra = NULL)
    {
        $engineInstance = View::getInstance()->getEngineByPath($tagBody);
        if ($engineInstance instanceof Base)
        {
            return sprintf('<? include "%s"; ?>', $engineInstance->getCompiledFile($tagBody));
        }
        return sprintf('<? echo \Tiny\MVC\View\View::getInstance()->fetch("%s") ?>', $tagBody);
    }

    /**
     * 解析date标签
     *
     * @param string $tagBody
     *            解析的标签设置
     * @param boolean $isCloseTag
     *            是否为闭合标签
     * @return string
     */
    protected function _parseDateTag($tagBody, $extra = NULL)
    {
        $tagBody = trim($tagBody);
        $tagNodes = explode('|', trim($tagBody));
        $time = trim($tagNodes[0]);
        if (!preg_match("/^\d+$/", $time))
        {
            $time = strtotime($time) ?: time();
            echo $time;
        }
        $format = trim($tagNodes[1]) ?: 'Y-m-d H:i';
        return sprintf('<? echo date("%s", %d);?>', $format, $time);
    }
}
?>