<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name ConsoleRequest.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月4日下午8:47:47
 * @Class List
 * @Function List
 * @History King 2017年4月4日下午8:47:47 0 第一次建立该文件
 *          King 2017年4月4日下午8:47:47 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Request;

use Tiny\Runtime\Environment;

/**
 * 控制器请求类
 *
 * @package Tiny.Application.Request
 * @since 2017年4月4日下午8:48:32
 * @final 2017年4月4日下午8:48:32
 */
class ConsoleRequest extends Request
{
    
    /**
     * 参数数组名
     *
     * @var array
     */
    public $argc;
    
    /**
     * 参数数组
     *
     * @var array
     */
    public $argv;
    
    /**
     * 环境数组名
     *
     * @var Environment
     */
    public $env;
    
    /**
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Request\Request::initData()
     */
    protected function initData()
    {
        $this->env = $this->application->get(Environment::class);
        $this->argv = $this->server['argv'];
        $this->argc = $this->server['argc'];
        
        $args = $this->parseParam($this->argv, $this->argc);
        $this->argv = array_merge($this->argv, $args);
        $this->param->merge($this->argv);
    }
    
    /**
     * 解析命令行参数
     *
     * @param array $argv
     * @param int $argc
     * @return array
     */
    protected function parseParam($argv, $argc)
    {
        $argument = [];
        if ($argc <= 1) {
            return $argument;
        }
        for ($i = 1; $i < $argc; $i++) {
            $arg = $argv[$i];
            if (!$this->routeContext && preg_match("/^\/?[a-zA-Z][a-zA-Z0-9]+(\/[a-zA-Z][a-zA-Z0-9]+)+$/", $arg)) {
                $this->routeContext = $arg;
            }
            if (preg_match('/^-[a-zA-Z0-9]$/', $arg) && ($i < $argc - 1 && $argv[$i + 1][0] != '-')) {
                $i++;
                $argument[$arg[1]] = $argv[$i];
                continue;
            }
            if (preg_match('/^(--|-)([a-z][a-z0-9\-]*)(=(.*))?$/i', $arg, $out)) {
                $argument[$out[2]] = $out[4] ?: true;
                continue;
            }
        }
        return $argument;
    }
}
?>