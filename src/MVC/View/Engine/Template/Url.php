<?php
/**
 *
 * @Copyright (C), 2013-, King.
 * @Name  Url.php
 * @Author  King
 * @Version  1.0
 * @Date: 2014-2-8上午1:00:17
 * @Description
 * @Class List
 *      1.
 *  @Function List
 *   1.
 *  @History
 *      <author>    <time>                        <version >   <desc>
 *        King      2014-2-8上午1:00:17       第一次建立该文件
 *        King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\View\Helper;

use Tiny\MVC\Request\WebRequest;

/**
* url辅助类
* @package Tiny.MVC.View.Helper
* @since 2014-2-8上午1:19:01
* @final  2014-2-8上午1:19:01
*/
class Url
{

	/**
	* 输出网址
	*
	* @param array 网址参数
	* @param string $mod 生成的url类型
	* @param string $suffix 当$mod = r时的网址后缀
	* @return string
	*/
	public static function get(array $params, $mod = 'r', $suffix = '.html')
	{
		switch ($mod)
		{
			case 'r' :
				$url = self::getRUrl($params, $suffix);
				break;
			default :
				$url = self::getDUrl($params);
		}
		return $url;
	}

	/**
	* 获取重写后的URL
	*
	* @param array $params url参数
	* @param string $suffix 后缀
	* @return string
	*/
	public static function getRUrl($params, $suffix)
	{
		$req = WebRequest::getInstance();
		$cp = $req->getControllerParam();
		$ap = $req->getActionParam();
		$c = (isset($params[$cp])) ? $params[$cp] : $req->getController();
		$a = (isset($params[$ap])) ? '/' . $params[$ap] : '';
		unset($params[$cp], $params[$ap]);
		$c = preg_replace_callback('/([\$A-Z])/', function ($ms)
		{
			return '-' . strtolower($ms[0]);
		}, $c);
		$q = array ();
		foreach ($params as $k => $p)
		{
			$q[] = $k;
			$q[] = $p;
		}
		$q = join('-', $q);
		if ($q)
		{
			$q = '/' . $q . $suffix;
		}
		return 'http://' . $req->host . '/' . $c . $a . $q;
	}

	public static function getDUrl($params)
	{
		$req = WebRequest::getInstance();
		$url = 'http://' . $req->host . $req->getServer('SCRIPT_NAME');
		if ($params)
		{
			$u = array ();
			foreach ($params as $k => $v)
			{
				$u[] = $k . '=' . $v;
			}
			$url .= '?' . join('&', $u);
		}
		return $url;
	}
}
?>