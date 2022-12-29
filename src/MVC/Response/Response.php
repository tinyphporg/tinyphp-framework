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
use Tiny\Lang\Lang;

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
     * 格式化输出JSON的数组
     *
     * @var array
     */
    protected $formatJSONConfig;
    
    /**
     * 设置应用实例
     *
     * @param ApplicationBase $app 当前应用实例
     */
    public function __construct(ApplicationBase $app)
    {
        $this->application = $app;
    }
    
    /**
     * 输出HTTP的Header 和 Body
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
     */
    public function appendBody($body)
    {
        $this->body .= $body;
    }
    
    /**
     * 清除所有缓冲的内容
     */
    public function clear()
    {
        $this->body = '';
    }
    
    /**
     * 将当前所有缓冲的输出发送到客户端，停止该页的执行。
     */
    public function end(string $msg = '')
    {
        if ($msg) {
            $this->appendBody($msg);
        }
        $this->output();
        die;
    }
    
    /**
     * 设置HTTP输出的Body
     *
     * @param string $string 字符串
     */
    public function write($string)
    {
        $this->body = $string;
    }
    
    /**
     * 以JSON格式输出
     *
     * @param mixed $input 输出数据
     */
    public function outJson($input)
    {
        $this->application->isDebug = false;
        $this->write(json_encode($input));
    }
    
    /**
     * 输出格式化
     *
     * @param int $status 状态码
     * @param string $param 可替换msg里面的% 最后一个参数如果是数组 则输出data
     * @example $this->response->formatJSON(0, 'msg1', 'msg2', ['aaa', 'aaaa']);
     *          {"status":0,"msg":"msg1msg2","data":["aaa","aaaa"]}
     */
    public function outFormatJSON($status = 0, ...$params)
    {
      // 关闭调试信息输出
      $this->application->isDebug = false;
      
      // 获取格式化的状态码
      if (!$this->formatJSONConfig) {
          $lang = $this->application->get(Lang::class);
          $configId = $this->application->properties['response.formatJsonConfigId'] ?: 'status';
          $this->formatJSONConfig = $lang->translate($configId);
      }
      
      // 状态码
      if (is_int($status) || (is_string($status) && preg_match('/\d+/', $status))) {
          $msg = (string)$this->formatJSONConfig[$status];
      }
      
      $messageBox =  [
          'status' => $status,
          'message' => $msg,
          'data' => [],
      ];
      
      // data 
      $popId = count($params) - 1;
      if ($params && $popId >=0 && is_array($params[$popId])) {
          $messageBox['data'] = array_pop($params);
      }
      
      // 附加数组输出
      $popId--;
      if ($params && $popId >= 0 && is_array($params[$popId])) {
          $messageBox = array_merge($messageBox, array_pop($params));
      }
      
      // 替换状态信息
      if ($params && $msg && strpos($msg, '%') !== false) {
            $messageBox['message'] = sprintf($msg, ...$params);
      }
        $this->write(json_encode($messageBox));
    }
    
    
    /**
     * 获取格式化的状态码信息数组
     * 
     * @return array
     */
    protected  function getFormatStatus()
    {
        if (!$this->formatJSONConfig) {
            $lang = $this->application->get(Lang::class);
            $configId = $this->application->properties['response.formatJsonConfigId'] ?: 'status';
            $this->formatJSONConfig = $lang->translate($configId);
        }
        return $this->formatJSONConfig;
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