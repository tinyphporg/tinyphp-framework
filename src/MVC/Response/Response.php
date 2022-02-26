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

use Tiny\MVC\Application\ApplicationBase;
use Tiny\Config\Configuration;

/**
 * 响应基类
 *
 * @package Tiny.Application.Response
 * @since 2017年3月13日下午1:47:14
 * @final 2017年3月13日下午1:47:14
 */
abstract class Response
{
    
    /**
     * 当前应用程序实例
     *
     * @var ApplicationBase
     */
    protected $application;
    
    /**
     * 默认编码
     *
     * @var string
     */
    protected $charset = 'utf-8';
    
    /**
     * 当前HTTP响应的输出流
     *
     * @var string
     */
    protected $body;
    
    /**
     * 格式化输出JSON的配置ID
     *
     * @var string
     */
    protected $formatJSONConfigId;
    
    /**
     * 格式化输出JSON的数组
     *
     * @var array
     */
    protected $formatJSONConfig;
    
    /**
     * 设置应用实例
     *
     * @param ApplicationBase $app 当前应用实例
     * @return void
     */
    public function __construct(ApplicationBase $app)
    {
        $this->application = $app;
    }
    
    /**
     * 输出HTTP的Header 和 Body
     *
     * @return void
     *
     */
    public function output()
    {
        echo $this->body;
    }
    
    /**
     * 添加内容到Body里去
     *
     * @param $body string 字符串
     * @return void
     */
    public function appendBody($body)
    {
        $this->body .= $body;
    }
    
    /**
     * 清除所有缓冲的内容
     *
     * @return void
     */
    public function clear()
    {
        $this->body = '';
    }
    
    /**
     * 将当前所有缓冲的输出发送到客户端，停止该页的执行。
     *
     * @return void
     */
    public function end()
    {
        die($this->body);
    }
    
    /**
     * 设置HTTP输出的Body
     *
     * @param string $string 字符串
     * @return void
     */
    public function write($string)
    {
        $this->body = $string;
    }
    
    /**
     * 以JSON格式输出
     *
     * @param mixed $input 输出数据
     * @return void
     */
    public function outJson($input)
    {
        $this->application->isDebug = false;
        $this->write(json_encode($input));
    }
    
    /**
     * 设置格式化输出JSON的配置实例
     *
     * @param Configuration $config 配置实例
     * @return void
     */
    public function setFormatJSONConfigId($configId = false)
    {
        $this->formatJSONConfigId = $configId;
    }
    
    /**
     * 输出格式化
     *
     * @param int $status 状态码
     * @param string $param 可替换msg里面的% 最后一个参数如果是数组 则输出data
     * @example $this->response->formatJSON(0, 'msg1', 'msg2', ['aaa', 'aaaa']);
     *          {"status":0,"msg":"msg1msg2","data":["aaa","aaaa"]}
     */
    public function outFormatJSON($status = 0, ...$param)
    {
        $this->application->isDebug = false;
        
        if (!$this->formatJSONConfig) {
            $config = $this->application->container->get('lang');
            $configId = $this->application->properies['response']['formatJsonConfigId'] ?: 'status';
            $this->formatJSONConfig = $config->translate($configId);
        }
        
        if (!isset($this->formatJSONConfig[$status])) {
            $msg = (count($param) && !is_array($param[0])) ? array_shift($param) : '';
        } else {
            $msg = $this->formatJSONConfig[$status];
        }
        $popId = count($param) - 1;
        $data = ($param && is_array($param[$popId])) ? array_pop($param) : [];
        if ($param && $msg && strpos($msg, '%') !== false) {
            
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
     * @param string $charset 编码
     * @return void
     * @example HttpResponse::getInstance()->setCharest('UTF-8');
     */
    public function setCharset($charset)
    {
        if ($charset) {
            $this->charset = $charset;
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
        return $this->body;
    }
    
    /**
     * 获取响应编码
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }
}
?>