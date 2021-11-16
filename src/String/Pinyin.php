<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Pinyin.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-6上午05:59:32
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          King 2013-12-6上午05:59:32 1.0 第一次建立该文件
 *          King 2020年02月24日上午11:42:00 stable 1.0 审定稳定版本
 */
namespace Tiny\String;

/**
 * 汉字转拼音类 2009年，为解决小说阅读和检索的问题。
 * 思路: 汉字GBK由两个ASCII字节组成，组成XY坐标系，寻找到对应XY坐标点的拼音即可。
 * 优点:快速，不重复，包含所有汉字。
 *
 * @package Tiny.String
 * @since 2013-12-6上午06:01:17
 * @final 2013-12-6上午06:01:17
 */
class Pinyin
{

    /**
     * 拼音与汉字对照数据库文件路径
     *
     * @var string
     */
    const PINYIN_DB_PATH = __DIR__ . '/__res/py.qdb';

    /**
     * 单个汉字拼音的最小长度
     *
     * @var int
     */
    const PINYIN_LENGTH_MIN = 1;

    /**
     * 单个汉字拼音的最大长度
     *
     * @var int
     */
    const PINYIN_LENGTH_MAX = 8;

    /**
     * 没有音调的汉字拼音字母对照表
     *
     * @var array
     */
    const PINYIN_NO_TONE = [
        'ā' => 'a',
        'á' => 'a',
        'ǎ' => 'a',
        'à' => 'a',
        'ɑ' => 'a',
        'ō' => 'o',
        'ó' => 'o',
        'ǒ' => 'o',
        'ò' => 'o',
        'ē' => 'e',
        'é' => 'e',
        'ě' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ī' => 'i',
        'í' => 'i',
        'ǐ' => 'i',
        'ì' => 'i',
        'ū' => 'u',
        'ú' => 'u',
        'ǔ' => 'u',
        'ù' => 'u',
        'ǖ' => 'v',
        'ǘ' => 'v',
        'ǚ' => 'v',
        'ǜ' => 'v',
        'ü' => 'v'
    ];

    /**
     * 是否加载到内存里面进行转换
     *
     * @var string
     */
    public static $memoryCacheEnabled = TRUE;


    /**
     * 缓存到内存的字节码数据映射
     *
     * @var string
     */
    protected static $_cacheData = NULL;

    /**
     * 缓存已经寻找的汉字拼音字节码
     *
     * @var array
     */
    protected static $_cacheWords = [];

    /**
     * 错误数组
     *
     * @var array
     */
    protected static $_errors = [];

    /**
     * 转变成带音标的拼音
     *
     * @param string $str
     *        待转换的汉字字符串
     * @param string $suffix
     *        拼音之间的分隔符
     * @return string
     */
    public static function transformToneMark(string $str, string $suffix = ' '): string
    {
        return self::_doTransform($str);
    }

    /**
     * 转换成首字母
     *
     * @param string $str
     *        待转换的汉字字符串
     * @param string $suffix
     *        拼音之间的分隔符
     * @return string
     */
    public static function transformFirst(string $str, string $suffix = ' '): string
    {
        return self::_doTransform($str, FALSE, TRUE, $suffix);
    }

    /**
     * 转换成拼音
     *
     * @param string $str
     * @param string $suffix
     * @return string
     */
    public static function transform(string $str, string $suffix = ' '): string
    {
        $tostr = self::_doTransform($str, FALSE, $suffix);
        return strtr($tostr, self::PINYIN_NO_TONE);
    }

    /**
     * 转换拼音
     *
     * @param string $str
     *        所需转换字符
     * @param bool $isToneMark
     *        是否保留音标 默认为FALSE
     * @param bool $isFirst
     *        是否只保留首字母 默认为FALSE
     * @param string $suffix
     *        尾缀,默认为空格
     * @param string $charset
     *        编码 默认为utf-8
     * @return string
     */
    protected static function _doTransform($str, $isFirst = FALSE, $suffix = ' ')
    {
        $str = trim($str);
        if (!$str)
        {
            return '';
        }
        $str = iconv('UTF-8', 'GBK//IGNORE', $str);
        if (NULL === self::$_cacheData && self::$memoryCacheEnabled)
        {
            if (!is_file(self::PINYIN_DB_PATH))
            {
                throw new PinyinException(sprintf('路径:%s不存在!', self::PINYIN_DB_PATH));
            }

            self::$_cacheData = file_get_contents(self::PINYIN_DB_PATH);
        }
        $tostr = self::$_cacheData ? self::_transformByMemory($str, $suffix, $isFirst) : self::_transformByIo($str, $suffix, $isFirst);
        return iconv('GBK', 'UTF-8', $tostr);
    }

    /**
     * 在内存里转换
     *
     * @param string $str
     *        待转换的字符串
     * @return string
     */
    protected static function _transformByMemory($str, $suffix, $isFirst)
    {
        $result = '';
        $strLength = strlen($str);
        for ($i = 0; $i < $strLength; $i++)
        {
            $ordHigh = ord(substr($str, $i, 1));
            if ($ordHigh <= 128)
            {
                $result .= substr($str, $i, 1);
                continue;
            }

            $ordLow = ord(substr($str, ++$i, 1));
            if (!self::$_cacheWords[$ordHigh][$ordLow])
            {
                $leng = ($ordHigh - 129) * ((254 - 63) * 8 + 2) + ($ordLow - 64) * 8;
                self::$_cacheWords[$ordHigh][$ordLow] = trim(substr(self::$_cacheData, $leng, 8));
            }
            $strtrLen = $isFirst ? 1 : 8;
            $result .= substr(self::$_cacheWords[$ordHigh][$ordLow], 0, $strtrLen) . $suffix;
        }
        return $result;
    }

    /**
     * 通过IO流转换拼音
     *
     * @param string $str
     *        待转换的字符串
     * @return string
     */
    protected static function _transformByIO($str, $suffix, $isFirst)
    {
        $result = '';
        $fh = fopen(self::PINYIN_DB_PATH, 'rb');
        if (!$fh)
        {
            throw new PinyinException(sprintf('open pinyin db file[%s] is faild!' ,self::PINYIN_DB_PATH));
        }

        $strLength = strlen($str);
        for ($i = 0; $i < $strLength; $i++)
        {
            $ordHigh = ord(substr($str, $i, 1));
            if ($ordHigh <= 128)
            {
                $result .= substr($str, $i, 1);
                continue;
            }
            $ordLow = ord(substr($str, ++$i, 1));
            if (!isset(self::$_cacheWords[$ordHigh][$ordLow]))
            {
                $leng = ($ordHigh - 129) * ((254 - 63) * 8 + 2) + ($ordLow - 64) * 8;
                fseek($fh, $leng);
                self::$_cacheWords[$ordHigh][$ordLow] = trim(fgets($fh, 8));
            }
            $strtrLen = $isFirst ? 1 : 8;
            $result .= substr(self::$_cacheWords[$ordHigh][$ordLow], 0, $strtrLen) . $suffix;
        }
        fclose($fh);
        return $result;
    }
}
?>