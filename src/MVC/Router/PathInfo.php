<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name PathInfo.php
 * @author King
 * @version Beta 1.0
 * @Date 2019年11月20日下午7:28:25
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2019年11月20日下午7:28:25 第一次建立该文件
 *          King 2019年11月20日下午7:28:25 修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\Router;

use Tiny\MVC\Request\Request;

/**
 * 路径路由
 *
 * @package Tiny.MVC.Router
 * @since 2019年11月26日下午2:07:07
 * @final 2019年11月26日下午2:07:07
 */
class PathInfo implements RouteInterface
{
    
    /**
     * URL正则
     *
     * @var string
     */
    const PATHINFO_REGEX = "/^(?:.*?\.php)?((?:\/[a-z][a-z0-9]*)*)(?:\/([a-z][a-z0-9]*)(?:\/|((?:\-[a-z][a-z0-9_]*\-[a-z0-9_%]+)*)(\.[a-z]+))?)?$/i";
    
    /**
     * 默认扩展名
     *
     * @var string
     */
    const DEFAULT_EXT = '.html';
    
    /**
     * 匹配时的扩展
     *
     * @var string
     */
    protected $matchedExt;
    
    /**
     * 解析存放URL参数的数组
     *
     * @var array
     */
    protected $params = [];
    
    /**
     * 检查该规则是否成功
     *
     * @param array $regRule 匹配规则
     * @param string $routerString 路由名称
     * @return bool
     */
    public function match(Request $request, string $routeString, array $routeRule = [])
    {
        $extName = $this->formatExt($routeRule['ext']);
        $matchedParams = $this->matchUri($extName, $routeString);
        if (!$matchedParams) {
            return false;
        }
        
        $this->matchedExt = $extName;
        list ($controllerName, $actionName, $paramText) = $matchedParams;
        if ($controllerName[0] == "/" || $controllerName[0] == "\\") {
            $controllerName = substr($controllerName, 1);
        }
        
        $params = [];
        if ($paramText) {
            $paramList = explode('-', $paramText);
            for ($i = 1; $i < count($paramList); $i++) {
                $params[$paramList[$i]] = $paramList[$i + 1];
                $i++;
            }
        }
        if ($controllerName) {
            $params['controller'] = $controllerName;
        }
        if ($actionName) {
            $params['action'] = $actionName;
        }
        
        $this->params = $params;
        return true;
    }
    
    /**
     * 获取路由解析后的URL参数
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     * 格式化扩展名
     *
     * @param string $ext
     *
     * @return string
     */
    protected function formatExt($ext)
    {
        // @formatter:off
        $ename = str_replace([' ', '.'], '', $ext);
        // @formatter:on
        
        if (!$ename) {
            $ename = self::DEFAULT_EXT;
        }
        return '.' . $ename;
    }
    
    /**
     * 匹配URI是否符合路由规则
     *
     * @access protected
     * @param string $ext 扩展名
     * @param string $routerString 路由字符串
     * @return mixed
     *
     */
    protected function matchUri($ext, $routeString)
    {
        if ($index = strpos($routeString, "?")) {
            $routeString = substr($routeString, 0, $index);
        }
        
        $out = [];
        if (!preg_match(self::PATHINFO_REGEX, $routeString, $out)) {
            return false;
        }
        
        if (!$out[2]) {
            $cparams = explode('/', substr($out[1], 1));
            $aname = array_pop($cparams);
            if (!$cparams) {
                return [$aname, ''];
            }
            $controllerName = join('/', $cparams);
            return [$controllerName, $aname];
        }
        
        // 检测扩展名
        if ($out[4] && $out[4] != $ext) {
            return false;
        }
        return array_slice($out, 1, 3);
    }
    
    /**
     * 根据参数重写并返回URL
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Router\IRouter::rewriteUri()
     */
    public function rewriteUri(Request $request, array $params = [], $isreWrited = true)
    {
        if (!$params) {
            return '/' . $controllerName . '/' . $actionName;
        }
        $uris = [];
        foreach ($params as $k => $v) {
            $uris[] = rawurlencode($k) . '-' . rawurlencode($v);
        }
        $uri = join('-', $uris);
        return '/' . $controllerName . '/' . $actionName . ($uris ? '-' . $uri . $this->_extName : '');
    }
}
?>