<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月9日下午7:56:06
 * @Class List
 * @Function List
 * @History King 2017年3月9日下午7:56:06 0 第一次建立该文件
 *          King 2017年3月9日下午7:56:06 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Response;

use Tiny\MVC\ApplicationBase;
use Tiny\Config\Configuration;

/**
 * 响应基类
 *
 * @package Tiny.Application.Response
 * @since 2017年3月13日下午1:47:14
 * @final 2017年3月13日下午1:47:14
 */
abstract class Base
{

    /**
     * 单例实例
     *
     * @var self
     */
    protected static $_instance;

    /**
     * 当前应用程序实例
     *
     * @var ApplicationBase
     */
    protected $_app;

    /**
     * 默认编码
     *
     * @var string
     */
    protected $_charset = 'utf-8';

    /**
     * 语言包
     */
    protected $_locale = '';

    /**
     * 当前HTTP响应的输出流
     *
     * @var string
     */
    protected $_body;

    /**
     * 格式化输出JSON的配置ID
     *
     * @var string
     */
    protected $_formatJSONConfigId;

    /**
     * 格式化输出JSON的数组
     *
     * @var array
     */
    protected $_formatJSONConfig;

    /**
     * 获取实例 单例模式
     *
     * @return Base
     */
    public static function getInstance()
    {
        if (!self::$_instance)
        {
            $className = static::class;
            self::$_instance = new $className();
        }
        return self::$_instance;
    }

    /**
     * 设置应用实例
     *
     * @param ApplicationBase $app
     *        当前应用实例
     * @return void
     */
    public function setApplication(ApplicationBase $app)
    {
        $this->_app = $app;
    }

    /**
     * 输出HTTP的Header 和 Body
     *
     * @return void
     *
     */
    public function output()
    {
        echo $this->_body;
    }

    /**
     * 添加内容到Body里去
     *
     * @param $body string
     *        字符串
     * @return void
     */
    public function appendBody($body)
    {
        $this->_body .= $body;
    }

    /**
     * 清除所有缓冲的内容
     *
     * @return void
     */
    public function clear()
    {
        $this->_body = '';
    }

    /**
     * 将当前所有缓冲的输出发送到客户端，停止该页的执行。
     *
     * @return void
     */
    public function end()
    {
        die($this->_body);
    }

    /**
     * 设置HTTP输出的Body
     *
     * @param string $string
     *        字符串
     * @return void
     */
    public function write($string)
    {
        $this->_body = $string;
    }

    /**
     * 以JSON格式输出
     *
     * @param mixed $input
     *        输出数据
     * @return void
     */
    public function outJson($input)
    {
        $this->_app->isDebug = false;
        $this->write(json_encode($input));
    }

    /**
     * 设置格式化输出JSON的配置实例
     *
     * @param Configuration $config
     *        配置实例
     * @return void
     */
    public function setFormatJSONConfigId($configId = FALSE)
    {
        $this->_formatJSONConfigId = $configId;
    }

    /**
     * 输出格式化
     *
     * @param int $status
     *        状态码
     * @param string $param
     *        可替换msg里面的% 最后一个参数如果是数组 则输出data
     * @example $this->response->formatJSON(0, 'msg1', 'msg2', ['aaa', 'aaaa']);
     *          {"status":0,"msg":"msg1msg2","data":["aaa","aaaa"]}
     */
    public function outFormatJSON($status = 0, ...$param)
    {
        $this->_app->isDebug = false;

        if (!$this->_formatConfig)
        {
            $config = $this->_app->getLang();
            $locale = $this->_locale;
            $configId = $this->_app->properies['response']['formatJsonConfigId'] ?: 'status';
            if ($locale)
            {
                $this->_formatJSONConfig = $config[$locale . '.' . $configId];
            }

            if (!$this->_formatJSONConfig)
            {
                $this->_formatJSONConfig = $config[$configId];
            }
        }

        if (!isset($this->_formatJSONConfig[$status]))
        {
            $msg = (count($param) && !is_array($param[0])) ? array_shift($param) : '';
        }
        else
        {
            $msg = $this->_formatJSONConfig[$status];
        }
        $popId = count($param) - 1;
        $data = ($param && is_array($param[$popId])) ? array_pop($param) : [];
        if ($msg && strpos($msg, '%') !== FALSE)
        {
            $msg = sprintf($msg, ...$param);
        }

        $this->write(json_encode([
            'status' => $status,
            'msg' => $msg,
            'data' => $data
        ]));
    }

    /**
     * 设置响应编码
     *
     * @param string $charset
     *        编码
     * @return void
     * @example HttpResponse::getInstance()->setCharest('UTF-8');
     */
    public function setCharset($charset)
    {
        if ($charset)
        {
            $this->_charset = $charset;
        }
    }

    /**
     * 设置语言包
     *
     * @param string $locale
     *        语言包名称
     *        编码
     * @return void
     */
    public function setLocale($locale)
    {
        if ($locale)
        {
            $this->_locale = $locale;
        }
    }

    /**
     * 获取输出的Body
     *
     * @return string
     *
     */
    public function getContent()
    {
        return $this->_body;
    }

    /**
     * 获取响应编码
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }
}
?>