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
     * ssh host
     * @var string
     */
    protected $host = '127.0.0.1';
    
    /**
     * ssh 连接端口
     * @var integer
     */
    protected $port = 22;
    
    /**
     * ssh 用户名
     * 
     * @var string
     */
    protected $user = 'root';
    
    /**
     *  ssh 登录密码
     * @var  string
     */
    protected $password;

    /**
     * 连接
     *
     * @var resource
     */
    protected $connection;

    /**
     * 构造函数初始化SSH链接参数
     *
     * @param array $policy
     * @return void
     */
    public function __construct(array $config = [])
    {
        if (!extension_loaded('ssh2')) {
            throw new \Exception('ssh2 is not loaded!');
        }
        $host = (string)$config['host'];
        if ($host) {
            $this->host = $host;
        }
        
        $port = (int)$config['port'];
        if ($port > 0) {
            $this->port = $port;
        }
        
        $user = (string)$config['user'];
        if ($user) {
            $this->user = $user;
        }
        
        $password = (string)$config['password'];
        if ($password) {
            $this->password = $password;
        }
    }

    /**
     * 执行远程SSH命令
     *
     * @param string $cmd
     *        执行命令
     * @return string
     */
    public function exec($cmd)
    {
        $connection = $this->connect();
        if (!$connection)
        {
            return $this->disconnect();
        }
        $stream = ssh2_exec($connection, $cmd);
        if (!$stream)
        {
            return $this->disconnect();
        }
        try
        {
            stream_set_blocking($stream, true);
            stream_set_timeout($stream, 30);
            $ret = stream_get_contents($stream);
        }
        catch (\Exception $e)
        {
            return $this->disconnect();
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
        $this->disconnect();
    }

    /**
     * 链接
     *
     * @return $connection
     */
    protected function connect()
    {
        if (!$this->connection)
        {
            $this->connection = ssh2_connect($this->host, $this->port);
            if (!$this->connection)
            {
                return false;
            }
            if (!ssh2_auth_password($this->connection, $this->user, $this->password))
            {
                return false;
            }
        }
        return $this->connection;
    }

    /**
     * 断开链接
     */
    protected function disconnect()
    {
        if (!$this->connection)
        {
            return false;
        }
        $this->exec('echo "EXITING" && exit');
        ssh2_disconnect($this->connection);
        $this->connection = null;
        return false;
    }
}
?>