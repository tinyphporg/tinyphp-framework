<?php
/**
 *
 * @copyright (C), 2011-, King.
 * @Name: Status.php
 * @Author: King
 * @Version: Beta 1.0
 * @Date: 2013-3-30下午03:03:58
 * @Description:
 * @Class List:
 *        1.
 * @Function List:
 *           1.
 * @History: <author> <time> <version > <desc>
 *           King 2013-3-30下午03:03:58 Beta 1.0 第一次建立该文件
 *           King 2020年6月1日14:21 stable 1.0.01 审定
 *
 */
namespace Tiny\MVC\Web;

/**
 * http响应的状态码
 *
 * @package Tiny.Application.Web
 * @since 2013-3-30下午03:04:17
 * @final 2013-3-30下午03:04:17
 *
 */
final class HttpStatus
{

    /**
     * 所有状态码
     *
     * @var array
     */
    protected static $_status = [
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    ];

    /**
     * 根据状态码获取状态全文
     *
     * @param int $status
     *        状态码
     * @return string
     */
    public static function get($status)
    {
        return self::$_status[$status];
    }

    /**
     * 设置状态码和对应的状态全文
     *
     * @param int $code
     *        状态码
     * @param string $status
     *        状态
     * @return void
     */
    public static function set($code, $status)
    {
        self::$_status[$code] = $status;
    }
}
?>