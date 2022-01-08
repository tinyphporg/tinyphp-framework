<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name WebResponse.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月9日下午7:55:18
 * @Class List
 * @Function List
 * @History King 2017年3月9日下午7:55:18 0 第一次建立该文件
 *          King 2017年3月9日下午7:55:18 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Response;

use Tiny\MVC\Web\HttpStatus;
use Tiny\MVC\Web\HttpMimeMapping;

/**
 * Web响应实例
 *
 * @package Tiny.Application.Response
 * @since 2017年3月13日下午2:19:46
 * @final 2017年3月13日下午2:19:46
 */
class WebResponse extends Response
{

    /**
     * JSON数组
     *
     * @var array
     */
    protected $_json = [];

    /**
     * 默认类型
     *
     * @var string
     */
    protected $_contentType = 'html';

    /**
     * 添加一个Header到标头中
     *
     * @param string $header
     *        header内容
     * @param bool $replace
     *        是否替换之前相同的标头
     * @return void
     */
    public function appendHeader($header, $replace = TRUE)
    {
        return header($header, (bool)$replace);
    }

    /**
     * 清理已经设置的header
     *
     * @param string $name
     *        header名称 默认为空，则清理全部header
     * @return void
     */
    public function removeHeader($name = NULL)
    {
        return header_remove($name);
    }

    /**
     * 获取已经设置的header列表
     *
     * @return array
     */
    public function getHeaders()
    {
        return headers_list();
    }

    /**
     * 设置响应类型
     *
     * @param string $type
     *        类型
     * @param string $charset
     *        编码
     * @return void
     */
    public function setContentType($type, $charset = NULL)
    {
        $this->_contentType = $type;
        $charset = $charset ?: $this->_charset;
        $type = ('html' == $type) ? 'text/html' : HttpMimeMapping::get($type);
        return header('Content-Type: ' . $type . '; charset=' . $charset);
    }

    /**
     * 获取响应类型
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * 设置响应状态码
     *
     * @param int $code
     *        状态码
     * @return int 代码
     */
    public function setStatusCode($code)
    {
        $status = HttpStatus::get((int)$code);
        if ($status)
        {
            $this->appendHeader($status);
        }
        return $status;
    }

    /**
     * 结束此次finish
     *
     * @return void
     */
    public function finishFastCgi()
    {
        return fastcgi_finish_request();
    }

    /**
     * 重定向URL
     *
     * @param string $url
     *        URL链接
     * @return bool
     */
    public function redirect($url)
    {
        $this->setStatusCode(302);
        return $this->appendHeader("Location: " . $url);
    }

    /**
     * 永久重定向
     *
     *
     * @param string $url
     *        URL链接
     * @return bool
     */
    public function finalRedirect($url)
    {
        $this->setStatusCode(301);
        return $this->appendHeader("Location: " . $url);
    }

    /**
     * 以JS Callback方式返回数据
     *
     * @param array $data
     *        输出的数据
     * @return void
     */
    public function outJsonp($data)
    {
        $callback = $this->_app->request->get["jsonpCallback"];
        $callback = trim($callback);
        $string = ($callback != '') ? "try{\n" . $callback . '(' . json_encode($data) . ");\n}catch(e){}\n" : json_encode($data);
        $this->write($string);
    }

    /**
     * 设置编码
     *
     * @param string $charset
     *        编码名
     * @return void
     */
    public function setCharset($charset)
    {
        if (!$charset)
        {
            return;
        }
        parent::setCharset($charset);
        $this->setContentType($this->_contentType, $charset);
    }
}
?>