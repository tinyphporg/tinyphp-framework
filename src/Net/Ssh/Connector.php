<?php
/**
 * SSH链接
 *
 * @copyright (C), 2013-, King.
 * @name Connector.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月14日下午3:11:05
 * @Class List
 * @Function List
 * @History King 2017年4月14日下午3:11:05 0 第一次建立该文件
 *          King 2017年4月14日下午3:11:05 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Net\Ssh;

/**
 * SSH连接器
 *
 * @package Tiny.Net.Ssh
 * @since 2017年4月14日下午3:11:36
 * @final 2017年4月14日下午3:11:36
 */
class Connector
{

    /**
     * 链接配置策略数组
     *
     * @var array
     */
    protected $_policy = [
        'user' => 'root',
        'port' => '22',
        'passwd' => 'jinweimei2',
        'host' => '127.0.0.1'
    ];

    /**
     * 连接
     *
     * @var string
     */
    protected $_connection = NULL;

    /**
     * 构造函数初始化SSH链接参数
     *
     * @param array $policy
     * @return void
     */
    public function __construct(array $policy = [])
    {
        $this->_policy = array_merge($this->_policy, $policy);
    }

    /**
     * 执行远程SSH命令
     *
     * @param string $execStr
     *        执行命令
     * @return string
     */
    public function exec($execStr)
    {
        $conn = $this->_connect();
        if (!$conn)
        {
            return $this->_disconnect();
        }
        $stream = ssh2_exec($conn, $execStr);
        if (!$stream)
        {
            return $this->_disconnect();
        }

        $ret = '';
        try
        {
            stream_set_blocking($stream, TRUE);
            stream_set_timeout($stream, 30);
            $ret = stream_get_contents($stream);
        }
        catch (\Exception $e)
        {
            return $this->_disconnect();
        }
        return $ret;
    }

    /**
     * 回收链接
     *
     * @return void
     */
    public function __destruct()
    {
        $this->_disconnect();
    }

    /**
     * 链接
     *
     * @param
     *        void
     * @return $connection
     */
    protected function _connect()
    {
        if ($this->_connection)
        {
            return $this->_connection;
        }

        $this->_connection = ssh2_connect($this->_policy['host'], $this->_policy['port']);
        if (!$this->_connection)
        {
            return FALSE;
        }
        if (!ssh2_auth_password($this->_connection, $this->_policy['user'], $this->_policy['passwd']))
        {
            return FALSE;
        }
        return $this->_connection;
    }

    /**
     * 断开链接
     *
     * @return void
     */
    protected function _disconnect()
    {
        if (!$this->_connection)
        {
            return FALSE;
        }
        $this->exec('echo "EXITING" && exit');
        ssh2_disconnect($this->_connection);
        $this->_connection = NULL;
        return FALSE;
    }
}
?>