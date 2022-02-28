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
	 * 
	 * @var string
	 */
	protected $host = '';

	/**
	 * Smtp协议端口
	 * 
	 * @var int
	 */
	protected $port = 25;

	/**
	 * 发送邮件的Socket连接超时时间
	 * 
	 * @var int
	 */
	protected $timeout = 3;

	/**
	 * 发送域名
	 * 
	 * @var string
	 */
	protected $hostName = '';

	/**
	 * 是否开启调试模式
	 * 
	 * @var bool
	 */
	protected $isDebug = false;

	/**
	 * 是否进行身份验证
	 * 
	 * @var bool
	 *
	 */
	protected $auth = false;

	/**
	 * 验证的登录用户名
	 * 
	 * @var string
	 */
	protected $username;

	/**
	 * 验证时的登录密码
	 * 
	 * @var string
	 */
	protected $password;

	/**
	 * Socket连接句柄
	 * 
	 * @var #resource
	 */
	protected $sock;

	/**
    * 附件数组
    * 
    * @var array
    */
	protected $attachments;

	/**
	* 是否采用html格式
	* 
	* @var bool
	*/
	protected $isBodyHtml = false;

	/**
	 * 构造函数
	 *
	 * @param string $host smtp服务器地址
	 * @param int $port smtp服务器端口
	 * @param bool $auth 是否需要验证
	 * @param string $username 用户名
	 * @param string $password 密码
	 *
	 */
	public function __construct($host = '', $port = 25, $auth = true, $username = '', $password = '')
	{
		$this->port = (int)$port;
		$this->host = $host;
		$this->auth = (bool)$auth;
		$this->username = $username;
		$this->password = $password;
		$this->hostname = "tinyphp-smtp-host"; // is used in HELO command
		$this->sock = false;
	}

	/**
	* 设置smtp的地址和端口
	*
	* @param string $host 服务器地址
	* @param int $port 端口
	* @return Smtp
	*/
	public function setServer(string $host, int $port = 25)
	{
		$this->host = $host;
		$this->port = (int)$port;
	}

	/**
	* 设置验证参数
	*
	* @param string $username smtp用户名
	* @param string $password 用户密码
	* @param bool $isAuth 是否验证
	* @return Smtp
	*/
	public function setAuth(string $username, string $password, $isAuth = true)
	{
		$this->username = $username;
		$this->password = $password;
		$this->auth = $isAuth;
		return $this;
	}

	/**
	* 设置邮件正文是否为html格式
	*
	* @param bool $isBodyHtml true  是
	*                         false 否
	* @return Smtp
	*/
	public function setBodyHtml(string $isBodyHtml)
	{
		$this->isBodyHtml = $isBodyHtml;
		return $this;
	}

	/**
	* 设置是否开启调试模式
	*
	* @param bool $isDebug 是否开启调试模式
	* @return Smtp
	*/
	public function setDebug(bool $isDebug)
	{
		$this->isDebug = $isDebug;
		return $this;
	}

	/**
    * 设置socket超时秒数
    *
    * @param int $timeout 秒数
    * @return Smtp
    */
	public function setTimeout(int $timeout)
	{
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 * 添加附件
	 *
	 * @param string $filename 文件名称
	 * @param string $content 文件内容
	 * @return Smtp
	 */
	public function addAttachment($filename, string $content)
	{
		if (is_array($filename))
		{
			$this->attachments = array_merge($this->attachments, $filename);
		}
		else
		{
			$this->attachments[$filename] = $content;
		}
		return $this;
	}

	/**
	 * 清理附加的附件路径
	 *
	 * @return Smtp
	 */
	public function clearAttachments()
	{
		$this->attachments = [];
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
	 * @param string $additionalHeaders 附送的Header头
	 * @return bool
	 */
	public function sendMail($to, $subject = null, $body = null, $cc = null, $bcc = null, $from = null, $fromName = null,  $additionalHeaders = null)
	{
		if (! $from)
		{
			$from = $this->username;
		}
		$mailFrom = $this->getAddress($this->getStripComment($from));
		$body = preg_replace("/(^|(\r\n))(\.)/", "$1.$3", $body);
		
		/* 邮件正文 */
		$b = '';
		/* 组装邮件头部 */
		$header = "MIME-Version:1.0\r\n";
		$bodyType = $this->isBodyHtml ? "Content-Type:text/html;charset=utf-8;\r\n" : "Content-Type:text/plain;charset=utf-8;\r\n";
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
		if (empty($this->attachments))
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
			foreach ($this->attachments as $file => $att)
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
		$to = explode(",", $this->getStripComment($to));
		if ($cc != "")
		{
			$to = array_merge($to, explode(",", $this->getStripComment($cc)));
		}
		if ($bcc != "")
		{
			$to = array_merge($to, explode(",", $this->getStripComment($bcc)));
		}
		
		$sent = true;
		foreach ($to as $rcptTo)
		{
			$rcptTo = $this->getAddress($rcptTo);
			if (! $this->open($rcptTo))
			{
				$sent = false;
				continue;
			}
			if (! $this->send($this->hostname, $mailFrom, $rcptTo, $b))
			{
				$sent = false;
			}
			fclose($this->sock);
		}
		return $sent;
	}

	/**
	 * 发送SMTP握手语
	 *
	 * @param $helo string 握手语
	 * @return bool
	 */
	private function send($helo, $from, $to, $b)
	{
		if (! $this->putcmd("HELO", $helo))
		{
			return false;
		}
		/* 如果进行权限认证 */
		if ($this->auth)
		{
			if (! $this->putcmd("AUTH LOGIN", base64_encode($this->username)))
			{
				return false;
			}
			if (! $this->putcmd("", base64_encode($this->password)))
			{
				return false;
			}
		} /* end of if($this->_auth) */
		if (! $this->putcmd("MAIL", "FROM:<" . $from . ">"))
		{
			return false;
		}
		if (! $this->putcmd("RCPT", "TO:<" . $to . ">"))
		{
			return false;
		}
		if (! $this->putcmd("DATA"))
		{
			return false;
		}
		if (! $this->putMessage($b))
		{
			return false;
		}
		if (! $this->eom())
		{
			return false;
		}
		if (! $this->putcmd("QUIT"))
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
	private function open($address)
	{
		return ('' == $this->host) ? $this->mx($address) : $this->relay();
	}

	/**
	 * 应答
	 *
	 * @return bool
	 */
	private function relay()
	{
		$this->sock = fsockopen($this->host, $this->port, $errorNo, $errorString, $this->timeout);
		if (! ($this->sock && $this->ok()))
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
	private function mx($address)
	{
		$domain = preg_replace("/^.+@([^@]+)$/", "$1", $address);
		if (!getmxrr($domain, $mxHosts))
		{
			return false;
		}
		foreach ($mxHosts as $host)
		{
			$this->sock = fsockopen($host, $this->port, $errno, $errstr, $this->timeout);
			if (! ($this->sock && $this->ok()))
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
	private function putMessage($messages)
	{
		fputs($this->sock, $messages);
		$this->debug("> " . str_replace("\r\n", "\n" . "> ", $messages . "\n> "));
		return true;
	}

	/**
	 * 邮件内容边界符
	 *
	 * @return bool
	 */
	private function eom()
	{
		fputs($this->sock, "\r\n.\r\n");
		$this->debug(". [EOM]\n");
		return $this->ok();
	}

	/**
	 * OK
	 *
	 * @return bool
	 */
	private function ok()
	{
		$response = str_replace("\r\n", "", fgets($this->sock, 512));
		$this->debug($response . "\n");
		if (! preg_match("/^[23]/", $response))
		{
			fputs($this->sock, "QUIT\r\n");
			fgets($this->sock, 512);
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
	private function putcmd($cmd, $arg = '')
	{
		if ($arg != "")
		{
			$cmd = ($cmd == '') ? $arg : $cmd . ' ' . $arg;
		}
		fputs($this->sock, $cmd . "\r\n");
		$this->debug('> ' . $cmd . "\n");
		return $this->ok();
	}

	/**
	 * 消除脚本格式
	 *
	 * @param string $address 地址
	 * @return string
	 */
	private function getStripComment($address)
	{
		return preg_replace("/\([^()]*\)/", "", $address);
	}

	/**
	 * 获取邮件地址
	 *
	 * @param string $address 地址
	 * @return string
	 */
	private function getAddress($address)
	{
		return preg_replace("/^.*<(.+)>.*$/", "$1", preg_replace("/([\s\t\r\n])+/", "", $address));
	}

	/**
	 * 输出调试信息
	 *
	 * @param $message
	 */
	private function debug($message)
	{
		echo $this->isDebug ? $message . '<br />' : null;
	}
}
?>