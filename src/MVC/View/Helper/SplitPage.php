<?php
/**
 *
 * @Copyright (C), 2011-, King.$i
 * @Name  SplitPages.php
 * @Author  King
 * @Version  Beta 1.0
 * @Date  Mon Jan 23 16:31:14 CST 2012
 * @Description 简单分页类
 * @Class List
 *  	1.SplitPage
 *  @Function List
 *   1.
 *  @History
 *      <author>    <time>                        <version >   <desc>
 *        King      Mon Jan 23 16:31:14 CST 2012  Beta 1.0           第一次建立该文件
 *        King 2020年6月1日14:21 stable 1.0.01 审定
 *
 */
namespace Tiny\MVC\View\Helper;

/**
 * 简单分页类
 *
 * @package Helper
 * @since Mon Jan 23 16:31:57 CST 2012
 * @final Mon Jan 23 16:31:57 CST 2012
 */
class SplitPage
{

    /**
     * 是否已经输出样式
     *
     * @var bool
     */
    protected static $_isOutStyle = false;

    /**
     * url前缀
     *
     * @var string
     */
    protected $url = '/';

    /**
     * 记录总数
     *
     * @var int
     */
    protected $_recTotal = 0;

    /**
     * 每页条数
     *
     * @var int
     */
    protected $_recSize = 20;

    /**
     * 页数Index
     *
     * @var int
     */
    protected $_index = 1;

    /**
     * 页码总数
     *
     * @var int
     */
    protected $_total = 0;

    /**
     * 分页显示的页数
     *
     * @var int
     */
    protected $_limit = 6;

    /**
     * 是否输出css样式
     *
     * @var bool 默认为true 输出 false为不输出
     */
    protected $_outCss = true;

    /**
     * 默认的颜色代码
     *
     * @var string
     */
    protected $_color = 'def';

    /**
     * 默认的参数
     *
     * @var array
     */
    protected $_defParams = array(
        'url' => "%d" ,
        'total' => 0 ,
        'size' => 20 ,
        'limit' => 6 ,
        'css' => true ,
        'pageid' => 1 ,
        'type' => 0 ,
        'color' => 'def'
    );

    /**
     * 颜色类型数组
     *
     * @var array
     */
    protected $_colors = array(
        'red' => array('#FF9D9F' ,'#FF9D9F') ,
        'orange' => array('#F30' ,'#F90') ,
        'green' => array('#b2e05d' ,'#004D00') ,
        'zong' => array('rgb(68, 35, 7)' ,'rgb(68, 35, 7)') ,
        'def' => array('#666' ,'#aaa')
    );

    /**
     * 构造函数
     *
     * @param array $params 分页参数
     * @return void
     */
    public function __construct(array $params = array())
    {
        $params = array_merge($this->_defParams, $params);
        $this->setUrl($params['url']);
        $this->setPage($params['total'], $params['size'], $params['pageid']);
        $this->setLimit($params['limit']);
        $this->setIsOutCss($params['css']);
        $this->setColor($params['color']);
    }

    /**
     * 设置URL
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        if ($url)
        {
            $this->_url = $url;
        }
    }

    /**
     * 设置记录总数
     *
     * @param int $total 记录总数
     * @return void
     */
    public function setPage($total, $size, $pageId)
    {
        if ($total > 0)
        {
            $this->_recTotal = (int) $total;
        }
        if ($pageId >= 1)
        {
            $this->_index = (int) $pageId;
        }
        if ($size > 0)
        {
            $this->_recSize = (int) $size;
        }
        $this->_total = ceil($this->_recTotal / $this->_recSize);
    }

    /**
     * 设置分页显示的页数
     *
     * @param int $limit 显示的分页数
     * @return void
     */
    public function setLimit($limit)
    {
        if ($limit > 0)
        {
            $this->_limit = (int) $limit;
        }
    }

    /**
     * 设置是否输出css
     *
     * @param void
     * @return void
     */
    public function setIsOutCss($isOut)
    {
        $this->_outCss = (bool) $isOut;
    }

    /**
     * 设置分页颜色
     *
     * @param string $color 颜色代码
     * @return void
     */
    public function setColor($color)
    {
        $this->_color = (! isset($this->_colors[$color])) ? 'def' : $color;
    }

    /**
     * 返回分页的html代码
     *
     * @param string $style 输出分页的样式
     * @return string
     */
    public function fetch($style = 'def')
    {
        if ($this->_total <= 1)
        {
            return '';
        }
        if (! $style || 'def' == $style)
        {
            return $this->_getBodyDef();
        }
        else
        {
            $func = '_getBody' . $style;
            return $this->$func();
        }
    }

    /**
     * 获取分页样式
     *
     * @param string $color 颜色值
     * @return string
     */
    protected function _getStyle($color)
    {
        if (! $this->_outCss || self::$_isOutStyle)
        {
            return '';
        }
        self::$_isOutStyle = true;
        $color = $this->_colors[$color];
        $borderColor = $color[0];
        $bgColor = $color[1];
        $string = '<!--分页样式开始-->' . "\r\n";
        $string .= '<style>' . "\r\n";
        $string .= 'div.pageBoxt a {border: #ddd 1px solid; padding:2px 5px;  color: #000;margin-right: 2px;text-decoration: none}' . "\r\n";
        $string .= 'div.pageBox a:hover {color:#000;border: #888 1px solid; background-color: #f2f2f2}' . "\r\n";
        ;
        $string .= 'div.pageBox a:active { border: #666 1px solid; color: #000;background-color: #e7e7e7}' . "\r\n";
        $string .= 'div.pageBox a.current {border: ' . $borderColor . ' 1px solid;  font-weight: bold;  color: #fff; margin-right: 2px; padding: 2px 5px;  background: ' . $bgColor . '}' . "\r\n";
        $string .= 'div.pageBox a.current:hover {color:' . $borderColor . "}\r\n";
        $string .= 'div.pageBox a{padding:2px 5px;border: #f3f3f3 1px solid; color: #000; margin-right: 2px;text-decoration:none;}' . "\r\n";
        $string .= '</style>' . "\r\n";
        $string .= '<!--分页样式结束-->' . "\r\n";
        return $string;
    }

    /**
     * 获取分页字符串
     *
     * @param string $link 超链接
     * @param int 页面总数
     * @param int $pageIndex 页面索引ID
     * @param string $pre 后缀
     * @return string
     */
    private function _getBodyDef()
    {
        $string = $this->_getStyle($this->_color);
        $url = $this->_url;
        $index = $this->_index;
        $pre = $this->_urlPre;
        $total = $this->_total;
        $limit = $this->_limit;
        if ($index > 1)  /*上一页*/
        {
            $backPage = $index - 1;
            $backLink = $this->_getUrl($url, $backPage);
            $backName = '&lt;';
        }
        else
        {
            $backLink = $this->_getUrl($url, 1);
            $backName = '&lt;&lt;';
        }
        if ($index < $total)  /*下一页*/
        {
            $nextPage = $index + 1;
            $nextLink = $this->_getUrl($url, $nextPage);
            $nextName = '&gt;';
        }
        else
        {
            $nextPage = $total;
            $nextLink = $this->_getUrl($url, $nextPage);
            ;
            $nextName = '&gt;&gt;';
        }
        /* 首页 */
        if ($index > 2)
        {
            $string .= '<a href="' . $this->_getUrl($url, 1) . '"  >&lt;&lt;</a>' . "\r\n";
        }
        /* 上一页 */
        $string .= '<a  id="backpage" href="' . $backLink . '" >' . $backName . '</a>' . "\r\n";
        /* 总分页大于10的情况 */
        if ($total >= $limit)
        {
            $page = ceil($index / $limit);
            $start = ($page - 1) * $limit + 1;
            $end = $page * $limit;
            for ($i = $start; $i <= $end; $i ++)
            {
                $string .= ($i == $index) ? '<a href="javascript:;" class="current"  >' . $i . '</a>' . "\r\n" : '<a href="' . $this->_getUrl($url, $i) . '" >' . $i . '</a>' . "\r\n";
            } /* end of for($i = $pageIndex... */
        }
        else
        {
            for ($i = 1; $i <= $total; $i ++)
            {
                $string .= ($i == $index) ? '<a href="javascript:;" class="current"  >' . $i . '</a>' . "\r\n" : '<a href="' . $this->_getUrl($url, $i) . '" >' . $i . '</a>' . "\r\n";
            } /* end of for ($i = 1; $i <= $pageCount; $i++) */
        } /* end of if ($pageCount > 6) */
        if ($index < $total)
        {
            $string .= '<a id="nextpage"  href="' . $nextLink . '" >' . $nextName . '</a>' . "\r\n";
        }
        $string .= "<input type=\"text\"  style=\"border:1px solid #ccc;margin:0px 2px;width:30px;height:17px\" id=\"gotopage\" />&nbsp;页<a href=\"" . $this->_getUrl($url, $total) . "\" onclick=\"javaScript:var pageid=parseInt(document.getElementById('gotopage').value);if(!pageid || pageid > " . $total . "){location.href='" . $url . $totalt . $pre . "';return;}location.href='" . $url . "'.replace('%d', document.getElementById('gotopage').value) + '" . $pre . "';return false\" >&gt;&gt;</a>";
        return "<div class=\"pageBox\">\r\n<a style=\"border:0;background:0\">(" . $total . "/<b>" . $index . "</b>)</a>" . $string . '</ul>' . '</div>' . "\r\n";
        ;
    }

    /**
     * 将url取代编码
     *
     * @param string $url 带有%d的url字符串
     * @param int $pageId 查找出来的$pageId
     * @return string
     */
    protected function _getUrl($url, $pageId)
    {
        return sprintf($url, $pageId);
    }
}
?>