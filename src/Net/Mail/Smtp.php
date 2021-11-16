<?php
/**
 * @Copyright (C), 2011-, King.$i
 * @Name Smtp.php
 * @Author King
 * @Version Beta 1.0
 * @Date Sun Jan 22 23:01:49 CST 2012
 * @Description
 * @Class List
 * 1. Smtp 发送邮件类 SMTP协议Socket实现
 * @Function List
 * 1.
 * @History
 * <author> <time> <version > <desc>
 * King Sun Jan 22 23:01:49 CST 2012 Beta 1.0 第一次建立该文件
 * King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Net\Mail;

/**
 * Socket协议实现的Smtp推送邮件类
 * @package Mail
 * @since Sun Jan 22 23:03:12 CST 2012
 * @final Sun Jan 22 23:03:12 CST 2012
 */
class Smtp
{

	/**
	 * 真实服务器路径
	 * @var string
	 * @access protected
	 */
	protected $_host = '';

	/**
	 * Smtp协议端口
	 * @var int
	 * @access protected
	 */
	protected $_port = 25;

	/**
	 * 发送邮件的Socket连接超时时间
	 * @var int
	 * @access protected
	 */
	protected $_timeout = 3;

	/**
	 * 发送域名
	 * @var string
	 * @access protected
	 */
	protected $_hostName = '';

	/**
	 * 是否开启调试模式
	 * @var bool
	 * @access protected
	 */
	protected $_isDebug = false;

	/**
	 * 是否进行身份验证
	 * @var bool
	 * @access protected
	 *
	 */
	protected $_auth = false;

	/**
	 * 验证的登录用户名
	 * @var string
	 * @access protected
	 */
	protected $_username;

	/**
	 * 验证时的登录密码
	 * @var string
	 * @access protected
	 */
	protected $_password;

	/**
	 * Socket连接句柄
	 * @var #resource
	 * @access protected
	 */
	protected $_sock;

	/**
    * 附件数组
    * @var array
    * @access protected
    */
	protected $_attachments;

	/**
	* 是否采用html格式
	* @var bool
	* @access protected
	*/
	protected $_isBodyHtml = false;

	/**
	 * 构造函数
	 *
	 * @param string $host smtp服务器地址
	 * @param int $port smtp服务器端口
	 * @param bool $auth 是否需要验证
	 * @param string $username 用户名
	 * @param string $password 密码
	 * @return void
	 *
	 */
	public function __construct($host = '', $port = 25, $auth = true, $username = '', $password = '')
	{
		$this->_port = (int)$port;
		$this->_host = $host;
		$this->_auth = (bool)$auth;
		$this->_username = $username;
		$this->_password = $password;
		$this->_hostname = "tiny-smtp-host"; // is used in HELO command
		$this->_sock = false;
	}

	/**
	* 设置smtp的地址和端口
	*
	* @param string $host 服务器地址
	* @param int $port 端口
	* @return Smtp
	*/
	public function setServer($host, $port)
	{
		$this->_host = $host;
		$this->_port = (int)$port;
	}

	/**
	* 设置验证参数
	*
	* @param string $username smtp用户名
	* @param string $password 用户密码
	* @param bool $isAuth 是否验证
	* @return Smtp
	*/
	public function setAuth($username, $password, $isAuth = true)
	{
		$this->_username = $username;
		$this->_password = $password;
		$this->_auth = $isAuth;
		return $this;
	}

	/**
	* 设置邮件正文是否为html格式
	*
	* @param bool $isBodyHtml true  是
	*                         false 否
	* @return Smtp
	*/
	public function setBodyHtml($isBodyHtml)
	{
		$this->_isBodyHtml = $isBodyHtml;
		return $this;
	}

	/**
	* 设置是否开启调试模式
	*
	* @param bool $isDebug 是否开启调试模式
	* @return Smtp
	*/
	public function setDebug($isDebug)
	{
		$this->_isDebug = $isDebug;
		return $this;
	}

	/**
    * 设置socket超时秒数
    *
    * @param int $timeout 秒数
    * @return Smtp
    */
	public function setTimeout($timeout)
	{
		$this->_timeout = $timeout;
		return $this;
	}

	/**
	 * 添加附件
	 *
	 * @param string $filename 文件名称
	 * @param string $content 文件内容
	 * @return Smtp
	 */
	public function addAttachment($filename, $content)
	{
		if (is_array($file))
		{
			$this->_attachments = array_merge($this->_attachments, $file);
		}
		else
		{
			$this->_attachments[$filename] = $content;
		}
		return $this;
	}

	/**
	 * 清理附加的附件路径
	 *
	 * @param void
	 * @return Smtp
	 */
	public function clearAttachments()
	{
		$this->_attachments = array ();
		return $this;
	}

	/**
	 * 发送邮件
	 *
	 * @param string $to 收件邮件地址 多个邮件地址可以,隔开
	 * @param string $from 发送邮件地址
	 * @param string $subject 邮件标题
	 * @param string $body 邮件主体
	 * @param string $mailtype 邮件类型 HTML和TEXT
	 * @param string $cc 抄送地址
	 * @param string $bcc
	 * @param string 附送的Header头
	 * @return bool
	 */
	public function sendMail($to, $subject = "", $body = "", $cc = "", $bcc = "", $from = '', $fromName = '')
	{
		if (! $from)
		{
			$from = $this->_username;
		}
		$mailFrom = $this->_getAddress($this->_getStripComment($from));
		$body = preg_replace("/(^|(\r\n))(\.)/", "$1.$3", $body);
		/* 邮件正文 */
		$b = '';
		/* 组装邮件头部 */
		$header = "MIME-Version:1.0\r\n";
		$bodyType = $this->_isBodyHtml ? "Content-Type:text/html;charset=utf-8;\r\n" : "Content-Type:text/plain;charset=utf-8;\r\n";
		$header .= "To: " . $to . "\r\n";
		if ($cc != "")
		{
			$header .= "Cc: " . $cc . "\r\n";
		}
		if ($fromName == '')
		{
			$fromName = $from;
		}
		$header .= "From: " . $fromName . "<" . $from . ">\r\n";
		$header .= "Subject: " . $subject . "\r\n";
		$header .= $additionalHeaders;
		$header .= "Date: " . date("r") . "\r\n";
		$header .= "X-Mailer:By Redhat (PHP/" . PHP_VERSION . ")\r\n";
		list($msec, $sec) = explode(" ", microtime());
		$header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mailFrom . ">\r\n";
		if (empty($this->_attachments))
		{
			$header .= $bodyType;
			$b = $header . "\r\n" . $body;
		}
		else
		{
			$bndp = md5(uniqid("")) . rand(1000, 9999);
			$bnd = md5(uniqid("")) . rand(1000, 9999);
			$header .= "Content-Type:multipart/mixed;boundary=" . $bndp . "\r\n\r\n";
			$header .= "Content-Transfer-Encoding:8bit\r\n\r\n";
			/* 正文和附件 */
			$b .= "--$bndp\r\n";
			$b .= $bodyType;
			$b .= "Content-Transfer-Encoding:printable\r\n\r\n";
			$b .= $body . "\r\n";
			foreach ($this->_attachments as $file => $att)
			{
				$b .= "--$bndp\r\n";
				$b .= "Content-Type:text/plain;charset=utf-88\r\n";
				$b .= "Content-Transfer-Encoding:base64\r\n";
				$b .= "Content-Disposition:attachment;filename=" . $file . "\r\n\r\n";
				$b .= base64_encode($att) . "\r\n";
			}
			$b = $header . $b;
			$this->clearAttachments();
		}
		$to = explode(",", $this->_getStripComment($to));
		if ($cc != "")
		{
			$to = array_merge($to, explode(",", $this->_getStripComment($cc)));
		}
		if ($bcc != "")
		{
			$to = array_merge($to, explode(",", $this->_getStripComment($bcc)));
		}
		$sent = true;
		foreach ($to as $rcptTo)
		{
			$rcptTo = $this->_getAddress($rcptTo);
			if (! $this->_open($rcptTo))
			{
				$sent = false;
				continue;
			}
			if (! $this->_send($this->_hostname, $mailFrom, $rcptTo, $b))
			{
				$sent = false;
			}
			fclose($this->_sock);
		}
		return $sent;
	}

	/**
	 * 发送SMTP握手语
	 *
	 * @param $helo string 握手语
	 * @return bool
	 */
	private function _send($helo, $from, $to, $b)
	{
		if (! $this->_putcmd("HELO", $helo))
		{
			return false;
		}
		/* 如果进行权限认证 */
		if ($this->_auth)
		{
			if (! $this->_putcmd("AUTH LOGIN", base64_encode($this->_username)))
			{
				return false;
			}
			if (! $this->_putcmd("", base64_encode($this->_password)))
			{
				return false;
			}
		} /* end of if($this->_auth) */
		if (! $this->_putcmd("MAIL", "FROM:<" . $from . ">"))
		{
			return false;
		}
		if (! $this->_putcmd("RCPT", "TO:<" . $to . ">"))
		{
			return false;
		}
		if (! $this->_putcmd("DATA"))
		{
			return false;
		}
		if (! $this->_putMessage($b))
		{
			return false;
		}
		if (! $this->_eom())
		{
			return false;
		}
		if (! $this->_putcmd("QUIT"))
		{
			return false;
		}
		return true;
	}

	/**
	 * 打开Smtp协议的Socket链接
	 *
	 * @param string $address 邮件地址
	 * @return bool
	 */
	private function _open($address)
	{
		return ('' == $this->_host) ? $this->_mx($address) : $this->_relay();
	}

	/**
	 * 应答
	 *
	 * @param void
	 * @return bool
	 */
	private function _relay()
	{
		$this->_sock = fsockopen($this->_host, $this->_port, $errorNo, $errorString, $this->_timeout);
		if (! ($this->_sock && $this->_ok()))
		{
			return false;
		}
		return true;
	}

	/**
	 * 没有真实IP时的握手
	 *
	 * @param string $address 邮件地址
	 * @return bool
	 */
	private function _mx($address)
	{
		$domain = preg_replace("/^.+@([^@]+)$/", "$1", $address);
		if (! getmxrr($domain, $mxHosts))
		{
			return false;
		}
		foreach ($mxHosts as $host)
		{
			$this->_sock = fsockopen($host, $this->_port, $errno, $errstr, $this->_timeout);
			if (! ($this->_sock && $this->_ok()))
			{
				continue;
			}
			return true;
		} /* end of foreach ($mxHosts as $host) */
		return false;
	}

	/**
	 * 压入Message
	 *
	 * @param $header string 文件头信息
	 * @param string $body 主体信息
	 * @return bool
	 */
	private function _putMessage($header)
	{
		fputs($this->_sock, $header);
		$this->_debug("> " . str_replace("\r\n", "\n" . "> ", $header . "\n> " . $body . "\n> "));
		return true;
	}

	/**
	 * 邮件内容边界符
	 *
	 * @param void
	 * @return bool
	 */
	private function _eom()
	{
		fputs($this->_sock, "\r\n.\r\n");
		$this->_debug(". [EOM]\n");
		return $this->_ok();
	}

	/**
	 * OK
	 *
	 * @param void
	 * @return bool
	 */
	private function _ok()
	{
		$response = str_replace("\r\n", "", fgets($this->_sock, 512));
		$this->_debug($response . "\n");
		if (! preg_match("/^[23]/", $response))
		{
			fputs($this->_sock, "QUIT\r\n");
			fgets($this->_sock, 512);
			return false;
		}
		return true;
	}

	/**
	 * 压入CMD
	 *
	 * @param string $cmd CMD内容
	 * @return string $arg 参数
	 */
	private function _putcmd($cmd, $arg = '')
	{
		if ($arg != "")
		{
			$cmd = ($cmd == '') ? $arg : $cmd . ' ' . $arg;
		}
		fputs($this->_sock, $cmd . "\r\n");
		$this->_debug('> ' . $cmd . "\n");
		return $this->_ok();
	}

	/**
	 * 消除脚本格式
	 *
	 * @param string $address 地址
	 * @return string
	 */
	private function _getStripComment($address)
	{
		return preg_replace("/\([^()]*\)/", "", $address);
	}

	/**
	 * 获取邮件地址
	 *
	 * @param string $address 地址
	 * @return string
	 */
	private function _getAddress($address)
	{
		return preg_replace("/^.*<(.+)>.*$/", "$1", preg_replace("/([\s\t\r\n])+/", "", $address));
	}

	/**
	 * 输出调试信息
	 *
	 * @param $message
	 * @return void
	 */
	private function _debug($message)
	{
		echo $this->_isDebug ? $message . '<br />' : null;
	}
}
?>