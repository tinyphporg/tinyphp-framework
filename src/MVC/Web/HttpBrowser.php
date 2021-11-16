 <?php
/**
 *
 * @copyright (C), 2011-, King.
 * @name HttpBrowser.php
 * @author King
 * @version Beta 1.0
 * @Date: 2012-11-27上午06:02:05
 * @Description
 * @Class List
 *        1.
 * @Function List
 *           1.
 * @History <author> <time> <version > <desc>
 *          King 2012-11-27上午06:02:05 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\Http;

/**
 *
 * @package 浏览器类
 * @since: 2012-11-27上午06:02:23
 * @final: 2012-11-27上午06:02:23
 */
class HttpBrowser
{

    /**
     *
     * 是否手机客户端的浏览器
     *
     * @return bool
     */
    public function isMobile()
    {
        $patt = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
        $patt .= "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
        $patt .= "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";
        $patt .= "symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
        $patt .= "jig\s browser|hiptop|ucweb|benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
        $patt .= ")/i";
        return isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE']) || preg_match($patt, strtolower($_SERVER['HTTP_USER_AGENT']));
    }
}
?>