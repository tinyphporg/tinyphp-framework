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

use Tiny\MVC\Request\Param\Readonly;

/**
 * 控制器请求类
 *
 * @package Tiny.Application.Request
 * @since 2017年4月4日下午8:48:32
 * @final 2017年4月4日下午8:48:32
 */
class ConsoleRequest extends Base
{

    /**
     * 命令行参数数组
     *
     * @var array
     */
    protected $_argv;

    /**
     * 命令行参数数量
     *
     * @var int
     */
    protected $_argc;

    /**
     * 命令行参数实例
     *
     * @var Readonly
     */
    public $param;

    /**
     * 路由URI
     *
     * @var string
     */
    protected $uri;

    /**
     * 获取路由字符串
     *
     * @return string
     */
    public function getRouterString()
    {
        return $this->_uri;
    }

    /**
     * 设置路由参数
     *
     * @param array $param
     *        参数
     * @return void
     */
    public function setRouterParam(array $param)
    {
        
        $this->param->merge($param);
    }

    /**
     * 魔术函数获取变量的值
     *
     * @param string $key
     *        变量名
     * @return mixed
     */
    protected function _magicGet($key)
    {
        $key = strtolower($key);
        switch ($key)
        {
            case 'server':
                return new Readonly($this->_server, FALSE);
            case 'env':
                return new Readonly($_ENV, FALSE);
            case 'path':
                return $this->_server['PATH'];
            case 'user':
                return $this->_server['USER'];
            case 'pwd':
                return $this->_server['PWD'];
            case 'lang':
                return $this->_server['LANG'];
            case 'php':
                return $this->_server['_'];
            case 'script':
                return $this->_server['PHP_SELF'];
        }
    }

    /**
     * 构造函数,初始化
     *
     * @return void
     */
    protected function __construct()
    {
        $this->_server = $_SERVER;
        $this->_argv = $this->_server['argv'];
        $this->_argc = $this->_server['argc'];
        $arguments = $this->_parseParam($this->_argv, $this->_argc);
        $this->_argv = array_merge($this->_argv, $arguments);
        $this->param = new Readonly($this->_argv);
    }

    /**
     * 解析命令行参数
     *
     * @param array $argv
     * @param int $argc
     * @return array
     */
    protected function _parseParam($argv, $argc)
    {
        $argument = [];
        if ($argc <= 1)
        {
            return $argument;
        }
        for ($i = 1; $i < $argc; $i++)
        {
            $arg = $argv[$i];
            if (!$this->_uri && preg_match("/^\/?[a-zA-Z][a-zA-Z0-9]+(\/[a-zA-Z][a-zA-Z0-9]+)+$/", $arg))
            {
                $this->_uri = $arg;
            }
            $out = '';
            if (preg_match("/^(\/?([a-zA-Z][a-zA-Z0-9]+\/)+)([a-zA-Z][a-zA-Z0-9]+)(=([0-9]+))?$/", $arg, $out))
            {
                $cname = substr($out[1], 0, -1);
                $n = intval($out[5]) ?: 1;
                $argument['daemons'][] = [
                    'c' => $cname,
                    'a' => $out[3],
                    'n' => $n
                ];
                continue;
            }
            if (preg_match('/^-[a-zA-Z0-9]$/', $arg) && ($i < $argc - 1 && $argv[$i + 1][0] != '-'))
            {
                $i++;
                $argument[$arg[1]] = $argv[$i];
                continue;
            }
            if (preg_match('/^(--|-)([a-z][a-z0-9\-]*)(=(.*))?$/i', $arg, $out))
            {
                $argument[$out[2]] = $out[4] ?: TRUE;
                continue;
            }
        }
        return $argument;
    }
}
?>