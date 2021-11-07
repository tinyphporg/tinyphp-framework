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
use Tiny\MVC\Response\WebResponse;
use const Tiny\MVC\TINY_MVC_RESOURCES;

/**
 *信息提示框
 *@package Tiny.Application.Viewer.Helper
 *@since 2013-3-30下午01:03:02
 *@final 2013-3-30下午01:03:02
 *
 */
class MessageBox implements IHelper
{
    const HELPER_NAME_LIST = [        
        'messagebox'
    ];
    
    /**
     * View 当前view实例
     * @var View
     */
    protected $_view;
    
    /**
     * 配置
     * @var array
     */
    protected $_config;
    
    /**
     * 提示框标题
     * 
     * @var string
     */
    protected $_subject = '提示信息';
    
    /**
     *  跳转间隔时间
     *  
     * @var  int
     */
    protected $_timeout = 5;
    
    /**
     * 模板文件
     * 
     * @var string
     */
    protected $_templateFile = TINY_MVC_RESOURCES . 'view/helper/messagebox.htm';
    
    /**
     * 设置View实例
     *
     * @param View $view
     */
    public function setViewHelperConfig(View $view, array $hconfig)
    {
        $this->_view = $view;
        $this->_config = $hconfig;
    }
    
    /**
     * 是否支持指定的helper名检索
     * @param string $hname
     */
    public function matchHelperByName($hname)
    {
        return in_array($hname, self::HELPER_NAME_LIST);
    }
    
    /**
     * 设置模板路径
     * @param string $tpath
     * @param  $isRealpath
     */
    public function setTimeplateFile($tpath, $isRealpath)
    {
        
    }
    
    
	/**
    *
    * 显示信息框
    * @param string $message 消息内容
    * @param string $url 跳转地址
    * @param string $subject 消息标题
    * @param string $timeout 跳转延时/秒
    * @return string
    */
	public function show($message, $toUrl = NULL, $subject = null, $timeout = null)
	{
		$subject = trim($subject) ?: $this->_subject;
		$toUrl = trim($toUrl);
		$timeout = (int)$timeout ?: $this->_timeout;
        
		$messageBox = [
		    'subject' => $subject,
		    'tourl' => $toUrl,
		    'timeout' => $timeout
		];
		$this->_view->display($this->_templateFile, $messageBox, TRUE);
	}
}
?>