<?php
/**
 *
 * @Copyright (C), 2011-, King.
 * @Name MessageBox.php
 * @Author  King
 * @Version  Beta 1.0
 * @Date: Sun Mar 11 18:18:17 CST 2012
 * @Description
 * @Class List
 * 1. 提示框显示类
 *  @Function List
 *   1.
 *  @History
 *      <author>    <time>                        <version >   <desc>
 *        King      Sun Mar 11 18:18:17 CST 2012  Beta 1.0           第一次建立该文件
 *        King 2020年6月1日14:21 stable 1.0.01 审定
 *
 */
namespace Tiny\MVC\View\Helper;

use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\View\View;
use Tiny\MVC\View\IHelper;

/**
 *信息提示框
 *@package Tiny.Application.Viewer.Helper
 *@since 2013-3-30下午01:03:02
 *@final 2013-3-30下午01:03:02
 *
 */
class MessageBox implements IHelper
{

    protected $_view;
    
    /**
     * 设置View实例
     *
     * @param View $view
     */
    public function setView(View $view)
    {
        $this->_view = $view;    
    }
    
    /**
     * 是否支持指定的helper名检索
     * @param string $hname
     */
    public function checkHelperName($hname)
    {
        return ($hname == 'messagebox') ? TRUE : FALSE;
    }
    
	/**
    * 提示框标题
    * @var string
    */
	protected static $_subject = '提示信息';

	/**
    *  跳转间隔时间
    * @var  int
    */
	protected static $_timeout = 5;

	/**
    *
    * 显示信息框
    * @param string $message 消息内容
    * @param string $url 跳转地址
    * @param string $subject 消息标题
    * @param string $timeout 跳转延时/秒
    * @return string
    */
	public static function show($message, $url = null, $subject = null, $timeout = null)
	{
		$subject = ($subject == null) ? self::$_subject : $subject;
		$url = ($url == null) ? WebRequest::getInstance()->referer : $url;
		$timeout = ($timeout == null) ? self::$_timeout : (int)$timeout;
		$_messageTemplate = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{$subject}</title>
<meta name="description" content="" />
<style>
html,body{padding:0;margin:0}
.messageBody{background:#e2e2e2;margin:0 auto;width:1024px}
.messageBox{height:500px;}
.messageMain{height:150px;width:550px;border:1px solid #000;margin:100px auto;background:#fff}
.messageTitle{font-size:13px;background:#93ddff;height:30px;line-height:30px;padding:0px 10px;border-bottom:1px solid #000}
.messageContent{padding:10px;height:70px}
.messageBottom{height:30px;padding-left:30px;line-height:30px;font-size:12px;}
.messageBottom a{color:blue}
#messageTimeout{color:red;font-weight:700;margin:0px 5px}
</style>
</head>
<body class="messageBody">

<div class="messageBox">
<div class="messageMain">
<div class="messageTitle">{$subject}</div>
<div class="messageContent"><span>{$message}</span></div>
<div class="messageBottom">请耐心等待<span id="messageTimeout">$timeout</span>秒，或点击<a href="$url">这里自动跳转</a>。</div>
</div>
</div>
<script type="text/javascript">
    function messageToUrl(timeout)
    {
        if (timeout == 0)
        {
            location.href = "$url";
            return;
        }

        document.getElementById("messageTimeout").innerHTML = timeout;
         timeout--;
        setTimeout("messageToUrl(" + timeout + ")", 1000);
}
messageToUrl($timeout);
</script>
</body>
</html>
EOT;
		die($_messageTemplate);
	}
}
?>