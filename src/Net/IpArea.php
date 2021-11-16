<?php
/**
 *
 * @copyright (C), 2011-, King
 * @name IpArea.php
 * @author King
 * @version Beta 1.0
 * @Date 2013-4-1下午02:43:04
 * @Description
 * @Class List
 *        1.
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King 2013-4-1下午02:43:04 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Net;

// IP库地址
define('IPAREA_PATH', __DIR__ . '/__res/qqwry.dat');

/**
 * 获取ip所在的地区和城市
 *
 * @author King
 * @package Net
 * @since 2013-4-1下午02:43:08
 * @final 2013-4-1下午02:43:08}
 */
class IpArea
{

    /**
     * IP AREA包路径地址
     *
     * @var string
     *
     */
    const IPAREA_PATH = IPAREA_PATH;

    /**
     * 默认编码
     *
     * @var string
     *
     */
    const IPAREA_CHARSET = 'UTF-8';

    /**
     * 获取IP
     *
     * @param string $ip
     *        ip地址
     * @return string
     */
    public static function get($ip)
    {
        return self::_get($ip);
    }

    /**
     * 根据域名获取地区信息
     *
     * @param string $domain
     *        域名
     * @return string
     */
    public static function getByDomain($domain)
    {
        return self::getByDomain($domain);
    }

    /**
     * 根据IP获取城市名
     *
     * @param string $ip
     *        IP地址
     * @return string
     */
    public static function getCity($ip)
    {
        return self::_getCity($ip);
    }

    /**
     * 取得地区名
     *
     * @param string $ip
     *        IP地址
     * @return string
     */
    protected static function _get($ip)
    {
        if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip))
        {
            return '';
        }

        $return = '';
        $iparray = explode('.', $ip);
        if ($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31)))
        {
            $return = 'LAN';
        }
        elseif ($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255)
        {
            $return = 'Invalid IP Address';
        }
        else
        {
            $return = self::_getIpAreaByQQWry($ip);
        }
        $return = iconv('gbk', 'utf-8', $return);
        return $return;
    }

    /**
     * 根据域名获取地区
     *
     * @param string $domain
     *        域名 不带HTTP://
     * @return string
     */
    protected static function _getByDomain($domain)
    {
        return self::_get(gethostbyname($domain));
    }

    /**
     * 根据IP获取城市名
     *
     * @param $ip string
     *        输出IP
     * @return string
     */
    protected static function _getCity($ip)
    {
        $address = self::_get($ip);
        $address = str_replace('city', '', $address);
        $localinfo['city'] = trim($address);
        $name = IPAREA_CHARSET == 'gbk' ? $localinfo['city'] : iconv('utf-8', 'gbk', $localinfo['city']);
        $name = str_replace('city', '', $name);
        return $localinfo;
    }

    /**
     * 使用QQWry.Dat ip数据包获取地区
     *
     * @param string $ip
     *        IP地址
     * @return string
     */
    protected static function _getIpAreaByQQWry($ip)
    {
        $fp = fopen(self::IPAREA_PATH, 'rb');
        $ip = explode('.', $ip);
        $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];
        if (!($dataBegin = fread($fp, 4)) || !($dataEnd = fread($fp, 4)))
        {
            return;
        }
        $ipbegin = implode('', unpack('L', $dataBegin));
        if ($ipbegin < 0)
        {
            $ipbegin += pow(2, 32);
        }
        $ipend = implode('', unpack('L', $dataEnd));
        if ($ipend < 0)
        {
            $ipend += pow(2, 32);
        }
        $ipAllNum = ($ipend - $ipbegin) / 7 + 1;
        $BeginNum = $ip2num = $ip1num = 0;
        $ipAddr1 = $ipAddr2 = '';
        $EndNum = $ipAllNum;
        while (($ip1num > $ipNum) || ($ip2num < $ipNum))
        {
            $Middle = intval(($EndNum + $BeginNum) / 2);
            fseek($fp, $ipbegin + 7 * $Middle);
            $ipData1 = fread($fp, 4);
            if (strlen($ipData1) < 4)
            {
                fclose($fp);
                return 'System Error';
            }
            $ip1num = implode('', unpack('L', $ipData1));
            if ($ip1num < 0)
            {
                $ip1num += pow(2, 32);
            }
            if ($ip1num > $ipNum)
            {
                $EndNum = $Middle;
                continue;
            }
            $dataSeek = fread($fp, 3);
            if (strlen($dataSeek) < 3)
            {
                fclose($fp);
                return 'System Error';
            }
            $dataSeek = implode('', unpack('L', $dataSeek . chr(0)));
            fseek($fp, $dataSeek);
            $ipData2 = fread($fp, 4);
            if (strlen($ipData2) < 4)
            {
                fclose($fp);
                return 'System Error';
            }
            $ip2num = implode('', unpack('L', $ipData2));
            if ($ip2num < 0)
            {
                $ip2num += pow(2, 32);
            }
            if ($ip2num < $ipNum)
            {
                if ($Middle == $BeginNum)
                {
                    fclose($fp);
                    return 'Unknown';
                }
                $BeginNum = $Middle;
            }
            /*
             * end of if ($ip2num < $ipNum)
             */
        }
        /*
         * end of while (($ip1num > $ipNum) || ($ip2num < $ipNum))
         */
        $ipFlag = fread($fp, 1);
        if ($ipFlag == chr(1))
        {
            $ipSeek = fread($fp, 3);
            if (strlen($ipSeek) < 3)
            {
                fclose($fp);
                return 'System Error';
            }
            $ipSeek = implode('', unpack('L', $ipSeek . chr(0)));
            fseek($fp, $ipSeek);
            $ipFlag = fread($fp, 1);
        }
        /*
         * end of if ($ipFlag == chr(1))
         */
        if ($ipFlag == chr(2))
        {
            $AddrSeek = fread($fp, 3);
            if (strlen($AddrSeek) < 3)
            {
                fclose($fp);
                return 'System Error';
            }
            $ipFlag = fread($fp, 1);
            if ($ipFlag == chr(2))
            {
                $AddrSeek2 = fread($fp, 3);
                if (strlen($AddrSeek2) < 3)
                {
                    fclose($fp);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
                fseek($fp, $AddrSeek2);
            }
            else
            {
                fseek($fp, -1, SEEK_CUR);
            }
            /*
             * end of if ($ipFlag == chr(2))
             */
            while (($char = fread($fp, 1)) != chr(0))
            {
                $ipAddr2 .= $char;
            }
            $AddrSeek = implode('', unpack('L', $AddrSeek . chr(0)));
            fseek($fp, $AddrSeek);
            while (($char = fread($fp, 1)) != chr(0))
            {
                $ipAddr1 .= $char;
            }
        }
        else
        {
            fseek($fp, -1, SEEK_CUR);
            while (($char = fread($fp, 1)) != chr(0))
            {
                $ipAddr1 .= $char;
            }
            $ipFlag = fread($fp, 1);
            if ($ipFlag == chr(2))
            {
                $AddrSeek2 = fread($fp, 3);
                if (strlen($AddrSeek2) < 3)
                {
                    fclose($fp);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
                fseek($fp, $AddrSeek2);
            }
            else
            {
                fseek($fp, -1, SEEK_CUR);
            }
            /*
             * end of if ($ipFlag == chr(2))
             */
            while (($char = fread($fp, 1)) != chr(0))
            {
                $ipAddr2 .= $char;
            }
        }
        /*
         * end of if ($ipFlag == chr(2))
         */
        if (preg_match('/http/i', $ipAddr2))
        {
            $ipAddr2 = '';
        }
        $ipaddr = "$ipAddr1 $ipAddr2";
        $ipaddr = preg_replace('/CZ88\.NET/is', '', $ipaddr);
        $ipaddr = preg_replace('/^\s*/is', '', $ipaddr);
        $ipaddr = preg_replace('/\s*$/is', '', $ipaddr);
        if (preg_match('/http/i', $ipaddr) || $ipaddr == '')
        {
            $ipaddr = 'Unknown';
        }
        fclose($fp);
        return '' . $ipaddr;
    }
}
?>