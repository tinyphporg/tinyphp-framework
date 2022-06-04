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
namespace Tiny\MVC\Router\Route;

use Tiny\MVC\Request\Request;

/**
 * 路径路由
 *
 * @package Tiny.MVC.Router
 * @since 2019年11月26日下午2:07:07
 * @final 2019年11月26日下午2:07:07
 */
class PathInfo implements RouteInterface, RewriteUriInterface
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
    const DEFAULT_EXT = 'html';
    
    /**
     * 匹配时的扩展
     *
     * @var string
     */
    protected $matchedExt = self::DEFAULT_EXT;
    
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
        $this->params = [];
        $extName = (string)$this->formatExt($routeRule['ext']);
        $values = (array)$routeRule['values'];
        
        // check regex
        $checkRegex = (string)$routeRule['checkRegex'];
        if ($checkRegex &&  !$this->checkPath($routeString, $checkRegex)) {
            return;
        }
        
        //
        if ($matchs = $this->matchPath($routeString, $extName)) {
            $this->matchedExt = $extName;
            $this->resolveMatchs($matchs, $values, $extName);
            return true;
        }
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
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\Router\Route\RouteInterface::rewriteUri()
     */
    public function rewriteUri(string $controllerName, string $actionName, array $params = [])
    {
        if (!$params) {
            return '/' . $controllerName . '/' . $actionName;
        }
        $uris = [];
        foreach ($params as $k => $v) {
            $uris[] = rawurlencode($k) . '-' . rawurlencode($v);
        }
        $uri = join('-', $uris);
        return '/' . $controllerName . '/' . $actionName . ($uris ? '-' . $uri . ($this->matchedExt ?: '.' . self::DEFAULT_EXT) : '');
    }
    
    /**
     * 解析匹配的数组
     *
     * @param array $matchs
     * @param array $values
     * @param string $ext
     */
    protected function resolveMatchs(array $matchs, array $values = [], $ext = null)
    {
        $params = [];
        
        // default params
        list($controllerName, $actionName, $paramText) = $this->formatMatchs($matchs, $ext);
        if ($controllerName[0] == "/" || $controllerName[0] == "\\") {
            $controllerName = substr($controllerName, 1);
        }
        if ($controllerName) {
            $params['controller'] = $controllerName;
        }
        if ($actionName) {
            $params['action'] = $actionName;
        }
        
        // extra params
        if ($paramText) {
            $this->resolveParamText($paramText, $params);
        }
        
        // reslove values
        if ($values) {
            $this->resloveValues($values, $matchs, $params);
        }

        $this->params = $params;
    }
    
    /**
     * 解析路径里的参数文本
     *
     * @param string $paramText
     * @param array $params
     */
    protected function resolveParamText($paramText, &$params)
    {
        if (!$paramText) {
            return;
        }
        
        $paramList = explode('-', $paramText);
        for ($i = 1; $i < count($paramList); $i++) {
            $value = $paramList[$i + 1];
            if (false !== strpos($value, '%')) {
                $value = rawurldecode($value);
            }
            $params[$paramList[$i]] = $value;
            $i++;
        }
    }
    
    /**
     * 解析值
     *
     * @param array $values
     * @param array $matchs
     * @param array $params
     */
    protected function resloveValues(array $values, array $matchs, &$params)
    {
        foreach ($values as $key => $value) {
            $val = preg_replace_callback('/\$([0-9]{1,2})/', function ($ms) use ($matchs) {
                $index = $ms[1];
                return key_exists($index, $matchs) ? $matchs[$index] : $ms[0];
            }, $value);
            $params[$key] = $val;
        }
    }
    
    /**
     * 格式化匹配数组
     *
     * @param array $matchs
     * @param string $ext
     * @return array
     */
    protected function formatMatchs($matchs, $ext = null)
    {
        if (!$matchs[1] && $matchs[2] && !$matchs[4]) {
            return [
                $matchs[2]
            ];
        }
        
        // @formatter:off
        if (!$matchs[2]) {
            $cps = explode('/', substr($matchs[1], 1));
            $name = array_pop($cps);
            if (!$cps) {
                return [$name, ''];
            }
            $cname = join('/', $cps);
            return [$cname, $name];
        }
        // 检测扩展名 @formatter:on
        if ($matchs[4] && $matchs[4] != $ext) {
            return [];
        }
        // @formatter:on
        return array_slice($matchs, 1, 3);
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
    protected function matchPath($routeString, $ext = '')
    {
        if ($index = strpos($routeString, "?")) {
            $routeString = substr($routeString, 0, $index);
        }
        
        // @formatter:off
        $matchs = [];
        if (!preg_match(self::PATHINFO_REGEX, $routeString, $matchs)) {
            return false;
        }
        return $matchs;
    }
    
    /**
     * check uri is matched route.rule.checkregex
     * @param string $routeString 路由上下文
     * @param string $checkRegex 检测正则
     * @return bool
     */
    protected function checkPath(string $routeString, string $checkRegex)
    {
        return preg_match($checkRegex, $routeString);
    }
}
?>