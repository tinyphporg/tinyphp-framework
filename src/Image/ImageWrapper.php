<?php
/**
 *
 * @copyright (C), 2011-, King.
 * @name Image.php
 * @author King
 * @version Beta 1.0
 * @Date: Sat Nov 12 23:16 52 CST 2011
 * @Description
 * @Class List
 *        1.VerificationCode 图片操作封装类
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Tue Jan 03 16:38:55 CST 2012 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\Image;

use Tiny\Tiny;

/**
 * 简单封装的一些图片方法类
 *
 * @package Tiny.Image
 * @since : 2013-4-2上午02:37:10
 * @final : 2013-4-2上午02:37:10
 */
class ImageWrapper
{
    
    /**
     * 从IO流获取图片对象
     *
     * @var int
     */
    const IMAGE_TYPE_STREAM = 0;
    
    /**
     * 从字符串获取图片对象
     *
     * @var int
     */
    const IMAGE_TYPE_STRING = 1;
    
    /**
     * 暂存的验证码session id
     *
     * @var string
     */
    protected static $sessionId = 'Tiny_Verify';
    
    /**
     * 获取图片信息
     *
     * @param string $imageFile 图片文件路径
     * @return array|false;
     */
    public static function getImageInfo($imgFile)
    {
        $imageInfo = getimagesize($imgFile);
        if (false === $imageInfo) {
            return false;
        }
        $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
        $imageSize = filesize($imgFile);
        $info = array(
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageType,
            'size' => $imageSize,
            'mime' => $imageInfo['mime']
        );
        return $info;
    }
    
    /**
     * 生成缩略图 缩略图会根据源图的比例进行缩略的，生成的缩略图格式是JPG
     *
     * @param string $srcfile 源文件名 或者字符串
     * @param $dstfile mixed 生成缩略图的文件名,扩展名必需为 ".jpg"
     * @param int $maxThumbWidth 缩略图最大宽度
     * @param int $maxThumbHeight 缩略图最大高度
     * @return bool
     */
    public static function getThumbnail($srcfile, $dstfile, $maxThumbWidth, $maxThumbHeight, $rate = null,
        $type = self::IMAGE_TYPE_STREAM)
    {
        $tow = $maxThumbWidth;
        $toh = $maxThumbHeight;
        $makeMax = 0;
        $maxtow = $maxThumbWidth;
        $maxtoh = $maxThumbHeight;
        if (($maxtow >= 300) && ($maxtoh >= 300)) {
            $makeMax = 1;
        }
        $im = '';
        if ($type == self::IMAGE_TYPE_STREAM && $data = getimagesize($srcfile)) {
            if ($data[2] == 1) {
                $makeMax = 0;
                if (function_exists("imagecreatefromgif")) /*Gif格式不需要处理*/
				{
                    $im = imagecreatefromgif($srcfile);
                }
            } elseif ($data[2] == 2) {
                if (function_exists("imagecreatefromjpeg")) {
                    $im = imagecreatefromjpeg($srcfile);
                }
            } elseif ($data[2] == 3) {
                if (function_exists("imagecreatefrompng")) {
                    $im = imagecreatefrompng($srcfile);
                }
            }
        } elseif ($type == self::IMAGE_TYPE_STRING && $data = strlen($srcfile)) {
            $im = imagecreatefromstring($srcfile);
        }
        if (!$im) {
            return;
        }
        $srcw = imagesx($im);
        $srch = imagesy($im);
        $towh = $tow / $toh;
        $srcwh = $srcw / $srch;
        if ($towh <= $srcwh) {
            $ftow = $tow;
            $ftoh = $ftow * ($srch / $srcw);
            $fmaxtow = $maxtow;
            $fmaxtoh = $fmaxtow * ($srch / $srcw);
        } else {
            $ftoh = $toh;
            $ftow = $ftoh * ($srcw / $srch);
            $fmaxtoh = $maxtoh;
            $fmaxtow = $fmaxtoh * ($srcw / $srch);
        }
        if (($srcw <= $maxtow) && ($srch <= $maxtoh)) {
            $makeMax = 0;
        }
        if (($srcw > $tow) || ($srch > $toh)) {
            if (function_exists("imagecreatetruecolor") && function_exists("imagecopyresampled") &&
                @$ni = imagecreatetruecolor($ftow, $ftoh)) {
                imagecopyresampled($ni, $im, 0, 0, 0, 0, $ftow, $ftoh, $srcw, $srch);
                if ($makeMax && @$maxni = imagecreatetruecolor($fmaxtow, $fmaxtoh)) {
                    imagecopyresampled($maxni, $im, 0, 0, 0, 0, $fmaxtow, $fmaxtoh, $srcw, $srch);
                }
            } elseif (function_exists("imagecreate") && function_exists("imagecopyresized") &&
                @$ni = imagecreate($ftow, $ftoh)) {
                imagecopyresized($ni, $im, 0, 0, 0, 0, $ftow, $ftoh, $srcw, $srch);
                if ($makeMax && @$maxni = imagecreate($fmaxtow, $fmaxtoh)) {
                    imagecopyresized($maxni, $im, 0, 0, 0, 0, $fmaxtow, $fmaxtoh, $srcw, $srch);
                }
            } else {
                return '';
            }
            if (function_exists('imagejpeg')) {
                imagejpeg($ni, $dstfile, $rate);
            } elseif (function_exists('imagepng')) {
                imagepng($ni, $dstfile, $rate);
            }
            imagedestroy($ni);
        }
        imagedestroy($im);
        return file_exists($dstfile);
    }
    
    /**
     * 生成图片验证码
     *
     * @param int $length 验证码长度
     * @param int $mode 模型 0:数字 1:小写字母 2:大写字母 3:字母与数字组合
     * @param string $type 指定图片类型，一般用默认值.
     * @param int $width 图片宽
     * @param int $height 图片高
     */
    public static function imgVerify($length = 6, $mode = 3, $type = 'png', $width = 80, $height = 30)
    {
        $randval = self::getRandString($length, $mode);
        $session = Tiny::getApplication()->getSession();
        $session->set(self::$sessionId, md5(strtolower($randval)));
        $width = ($length * 9 + 10) > $width ? ($length * 9 + 10) : $width;
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = @imagecreatetruecolor($width, $height);
        } else {
            $im = @imagecreate($width, $height);
        }
        $r = [225, 255, 255, 223];
        $g = [225, 236, 237, 255];
        $b = [225, 236, 166, 125];
        $key = mt_rand(0, 3);
        $backColor = imagecolorallocate($im, $r[$key], $g[$key], $b[$key]); // 背景色（随机）
        $borderColor = imagecolorallocate($im, 100, 100, 100); // 边框色
        $pointColor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)); // 点颜色
        @imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
        @imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        // 干扰
        
        for ($i = 0; $i < 10; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44,
                $fontcolor);
        }
        for ($i = 0; $i < 25; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $pointColor);
        }
        @imagestring($im, 6, 12, 8, $randval, $stringColor);
        header("Content-type: image/" . $type);
        $ImageFun = 'Image' . $type;
        $ImageFun($im);
        imagedestroy($im);
    }
    
    /**
     * 生成图片验证码
     *
     * @param int $length :验证码长度
     * @param int $mode :模型 0:数字 1:小写字母 2:大写字母 3:字母与数字组合
     * @param string $type :指定图片类型，一般用默认值.
     * @param int $width :图片宽
     * @param int $height :图片高
     */
    public static function images($length = 6, $mode = 3, $type = 'png', $width = 80, $height = 30)
    {
        $randval = self::getRandString($length, $mode);
        $session = Tiny::getApplication()->getSession();
        $session->set(self::$_sessionId, md5(strtolower($randval)));
        
        $width = ($length * 9 + 10) > $width ? ($length * 9 + 10) : $width;
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $im = @imagecreatetruecolor($width, $height);
        } else {
            $im = @imagecreate($width, $height);
        }
        
        $r = [225, 255, 255, 223];
        $g = [225, 236, 237, 255];
        $b = [225, 236, 166, 125];
        $key = mt_rand(0, 3);
        $backColor = imagecolorallocate($im, $r[$key], $g[$key], $b[$key]); // 背景色（随机）
        $borderColor = imagecolorallocate($im, 100, 100, 100); // 边框色
        $pointColor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)); // 点颜色
        @imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
        @imagerectangle($im, 0, 0, $width - 1, $height - 1, $borderColor);
        $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        // 干扰
        
        for ($i = 0; $i < 10; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(30, 300), mt_rand(20, 200), 55, 44,
                $fontcolor);
        }
        for ($i = 0; $i < 25; $i++) {
            $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $pointColor);
        }
        
        @imagestring($im, 6, 12, 8, $randval, $stringColor);
        header("Content-type: image/" . $type);
        $ImageFun = 'Image' . $type;
        $ImageFun($im);
        imagedestroy($im);
    }
    
    /**
     * 检测输入的验证码是否正确
     *
     * @param string $verifyCode 验证码
     * @return bool
     */
    public static function chkVerify($verifyCode)
    {
        $session = Tiny::getApplication()->getSession();
        $res = $session->get(self::$sessionId) == md5(strtolower($verifyCode));
        self::cleanVerify();
        return $res;
    }
    
    /**
     * 清理验证码的session
     *
     * @param void
     * @return void
     */
    public static function cleanVerify()
    {
        Tiny::getApplication()->getSession->remove(self::$sessionId);
    }
    
    /**
     * 获取一些随机字符串 主要为生成验证码
     *
     * @param int $length 随机字符串的长度
     * @param $int $mod 随机字符串的类型
     *        0 大小写字母+数字
     *        1 小写字母+数字
     *        2 纯数字型
     *        3 数字和大写字母型
     * @return string
     *
     */
    public static function getRandString($length = 4, $mod = 3)
    {
        $string = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
        $stringNum = "0123456789";
        $lowerString = "abcdefghijklmnpqrstuvwxyz";
        if ($mod == 0) {
            $string .= $lowerString . $stringNum;
        } elseif ($mod == 1) {
            $string = $lowerString . $stringNum;
        } elseif ($mod == 2) {
            $string = $stringNum;
        } elseif ($mod == 3) {
            $string .= $stringNum;
        }
        $lengthTotal = strlen($string) - 1;
        $stringRequest = '';
        for ($i = 0; $i < $length; $i++) {
            $stringRequest .= $string[rand(0, $lengthTotal)];
        }
        return $stringRequest;
    }
}
?>