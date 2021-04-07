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
namespace Tiny\MVC\Viewer;

use Tiny\MVC\Viewer\Helper\Url;

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
     * 变量正则
     *
     * @var string
     */
    protected $var_regexp = "\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*";

    /**
     * 对象正则
     *
     * @var string
     */
    protected $object_regexp = "\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*(\-\>\(.*?)*";

    /**
     * 标签正则
     *
     * @var string
     */
    protected $vtag_regexp = "\<\?=(\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\?\>";

    /**
     * 常量正则
     *
     * @var string
     */
    protected $const_regexp = "\{([\w]+)\}";

    /**
     * 获取输出的html内容
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Viewer\IViewer::fetch()
     */
    public function fetch($file, $isAbsolute = FALSE)
    {
        ob_start();
        extract($this->_variables, EXTR_SKIP);
        include $this->_getCompilePath($file, $isAbsolute);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
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
    protected function _getCompilePath($file, $isAbsolute = FALSE)
    {
        $path = $isAbsolute ? $file : $this->_templateFolder . $file;

        if (!is_file($path))
        {
            throw new ViewerException(sprintf("viewer error: the template file %s is not exists!", $path));
        }

        $compilePath = $this->_compileFolder . md5($path) . '.php';
        if (file_exists($compilePath) && filemtime($compilePath) > filemtime($path))
        {
            return $compilePath;
        }

        if (!$fh = fopen($path, 'rb'))
        {
            throw new ViewerException("viewer error: fopen $path is faild");
        }

        flock($fh, LOCK_SH);
        $templateContent = fread($fh, filesize($path));
        flock($fh, LOCK_UN);
        fclose($fh);

        $compileContent = $this->_parseTemplate($templateContent);
        $ret = file_put_contents($compilePath, $compileContent, LOCK_EX);
        if (!$ret)
        {
            throw new ViewerException(sprintf("viewer compile error: file_put_contents %s is faild", $compilePath));
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
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = preg_replace_callback("/\{url\|([^\}|]+)\|?([a-z]+)?\}/is", [
            $this,
            "_resolvUrl"
        ], $template);
        $template = preg_replace("/\{(" . $this->var_regexp . "(\->.*?)*)\}/", "<?=\\1?>", $template);
        $template = preg_replace("/\{(" . $this->const_regexp . ")\}/", "<?=\\1?>", $template);
        $template = preg_replace("/(?<!\<\?\=|\\\\)$this->var_regexp/", "<?=\\0?>", $template);
        $template = preg_replace_callback("/\<\?=(\@?\\\$[a-zA-Z_]\w*)((\[[\\$\[\]\w]+\])+)\?\>/is", [
            $this,
            "_arrayindex"
        ], $template);
        $template = preg_replace_callback("/\{\{eval (.*?)\}\}/is", [
            $this,
            "_stripEvalTag"
        ], $template);
        $template = preg_replace_callback("/\{eval (.*?)\}/is", [
            $this,
            "_stripEvalTag"
        ], $template);
        $template = preg_replace_callback("/\{for (.*?)\}/is", [
            $this,
            '_stripForTag'
        ], $template);
        $template = preg_replace_callback("/\{elseif\s+(.+?)\}/is", [
            $this,
            "_stripElseIfTag"
        ], $template);
        $template = preg_replace_callback("/\{date\s+(.+?)\}/is", [
            $this,
            "_date"
        ], $template);

        for ($i = 0; $i < 4; $i++)
        {
            $template = preg_replace_callback("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/is", [
                $this,
                "_loopsection"
            ], $template);
            $template = preg_replace_callback("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/is", [
                $this,
                "_dLoopsection"
            ], $template);
        }
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

        $template = "<? if(!defined('IN_ZEROAI_VIEW_TEMPLATE')) exit('Access Denied');?>\r\n$template";
        $template = preg_replace("/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i", "\\1'\\2']", $template);
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);

        return $template;
    }

    /**
     * 解析eval标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     */
    protected function _stripEvalTag($match)
    {
        return "<? " . $this->_stripvtag($match) . ' ?>';
    }

    /**
     * if标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _stripIfTag($match)
    {
        return '<? if(' . $this->_stripvtag($match) . ') { ?>';
    }

    /**
     * 过滤标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _stripForTag($match)
    {
        return '<? for (' . $this->_stripvtag($match) . ') { ?>';
    }

    /**
     * ELSE标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _stripElseIfTag($match)
    {
        return '<? } elseif(' . $this->_stripvtag($match) . ') { ?>';
    }

    /**
     * include标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _stripvIncludeTag($match)
    {
        return '<? include $this->_getCompilePath("' . $this->_stripvtag($match) . '"); ?>';
    }

    /**
     * 解析数组索引
     *
     * @param string $name
     *        索引名称
     * @param array $items
     *        解析成的实体
     * @return void
     *
     */
    protected function _arrayindex($match)
    {
        $name = $match[1];
        $items = $match[2];
        $items = preg_replace("/\[([a-zA-Z_]\w*)\]/is", "['\\1']", $items);
        return "<?=${name}${items}?>";
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
     * 变量标签
     *
     * @param string $match
     *        匹配字符串
     * @return string
     */
    protected function _doStripvtag($match)
    {
        return preg_replace("/$this->vtag_regexp/is", "\\1", str_replace("\\\"", '"', $match));
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
     * 循环标签
     *
     * @param string $match
     *        匹配字符串
     * @return void
     */
    protected function _dLoopsection($match)
    {
        return $this->_loopsection([
            $match[1],
            '',
            $match[2],
            $match[3]
        ]);
    }

    /**
     * 解析遍历数组循环
     *
     * @param string $match
     *        匹配字符串
     * @return string
     *
     */
    protected function _loopsection($match)
    {
        $arr = $this->_doStripvtag($match[1]);
        $k = $this->_doStripvtag($match[2]);
        $v = $this->_doStripvtag($match[3]);
        $statement = $match[4];
        return $k ? "<? foreach((array)$arr as $k => $v) {?>$statement<? }?>" : "<? foreach((array)$arr as $v) {?>$statement<? } ?>";
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
        $param = $match[1];
        $type = $match[2];
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