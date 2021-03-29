<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name WebApplication.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月8日下午4:02:43
 * @Class List
 * @Function List
 * @History King 2017年3月8日下午4:02:43 0 第一次建立该文件
 *          King 2017年3月8日下午4:02:43 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC;

use Tiny\MVC\Request\WebRequest;
use Tiny\MVC\Response\WebResponse;
use Tiny\MVC\Web\Session\HttpSession;
use Tiny\MVC\Web\HttpCookie;

/**
 * WEB应用程序实例
 *
 * @author King
 * @package Tiny。MVC
 * @since 2013-3-21下午04:55:41
 * @final 2013-3-21下午04:55:41
 */
class WebApplication extends ApplicationBase
{


    /**
     * cookie对象
     *
     * @var HttpCookie
     */
    protected $_cookie;

    /**
     * Session对象
     *
     * @var HttpSession
     */
    protected $_session;

    /**
     * 获取http对话信息
     *
     * @return HttpSession
     */
    public function getSession()
    {
        if (!$this->_session)
        {
            $this->_session = HttpSession::getInstance($this->_prop['session']);
        }
        return $this->_session;
    }

    /**
     * 获取Cookie对象
     *
     * @param array $data 预设cookies
     * @return HttpCookie
     */
    public function getCookie($data = NULL)
    {
        if (!$this->_cookie)
        {
            $this->_cookie = HttpCookie::getInstance($data);
            $prop = $this->_prop['cookie'];
            $this->_cookie->setDomain($prop['domain']);
            $this->_cookie->setExpires((int)$prop['expires']);
            $this->_cookie->setPrefix((string)$prop['prefix']);
            $this->_cookie->setPath($prop['path']);
            $this->_cookie->setEncode($prop['encode']);
        }
        return $this->_cookie;
    }

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
}
?>