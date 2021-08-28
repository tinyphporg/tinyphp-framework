<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ConsoleApplication.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月5日下午11:30:52
 * @Class List
 * @Function List
 * @History King 2017年4月5日下午11:30:52 0 第一次建立该文件
 *          King 2017年4月5日下午11:30:52 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC;

use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Response\WebResponse;
use Tiny\MVC\Web\Session\HttpSession;
use Tiny\MVC\Web\HttpCookie;

/**
 * 命令行应用实例
 *
 * @package Tiny.Application
 * @since 2017年4月5日下午11:31:23
 * @final 2017年4月5日下午11:31:23
 */
class WebApplication extends ApplicationBase
{

    /**
     * cookie对象
     * @var HttpCookie
     * @access protected
     */
    protected $_cookie;
    
    /**
     * Session对象
     * @var HttpSession
     * @access portected
     */
    protected $_session;
    
    /**
     * 初始化请求实例
     *
     * @return void
     */
    protected function _initRequest()
    {
        $this->request = WebRequest::getInstance();
        parent::_initRequest();
    }

    /**
     * 初始化响应实例
     *
     * @return void
     */
    protected function _initResponse()
    {
        $this->response = WebResponse::getInstance();
        parent::_initResponse();
    }
    
    /**
     * 获取http对话信息
     *
     * @param void
     * @return HttpSession
     */
    public function getSession()
    {
        if (! $this->_session)
        {
            $this->_session = HttpSession::getInstance($this->_prop['session']);
        }
        return $this->_session;
    }
    
    /**
     * 获取Cookie对象
     *
     * @param void
     * @return HttpCookie
     */
    public function getCookie()
    {
        if (!$this->_cookie)
        {
            $policy = $this->_prop['cookie'];
            
            //初始化data
            $requestData = $this->request->getRequestData();
            if (!is_array($policy['data']))
            {
                $policy['data'] = $requestData['cookie'];
            }
            else 
            {
                $policy['data'] = array_merge($policy['data'], $requestData['cookie']);
            }
            
            $this->_cookie = HttpCookie::getInstance($policy);
        }
        return $this->_cookie;
    }
}
?>