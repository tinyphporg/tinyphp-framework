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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 *
 */
namespace Tiny\MVC\Router;

/**
 * 路径路由
 *
 * @package Tiny.MVC.Router
 * @since 2019年11月26日下午2:07:07
 * @final 2019年11月26日下午2:07:07
 */
class PathInfo implements IRouter
{
    
    /**
     * URL正则
     * 
     * @var string
     */
    const PATHINFO_PATTERN = "/^(?:.*?\.php)?((?:\/[a-z][a-z0-9]*)*)(?:\/([a-z][a-z0-9]*)(?:\/|((?:\-[a-z][a-z0-9_]*\-[a-z0-9_%]+)*)(\.[a-z]+))?)?$/i";
    
    /**
     * 默认扩展名
     * 
     * @var string
     */
    const DEFAULT_EXT  = '.html';
    
    /**
     * 匹配时的扩展名
     * 
     * @var string
     */
    protected $_extName;
    
    /**
     * 解析存放URL参数的数组
     *
     * @var array
     */
    protected $_params = [];

    /**
     * 检查该规则是否成功
     *
     * @param array $regRule
     *        匹配规则
     * @param string $routerString
     *        路由名称
     * @return bool
     */
    public function checkRule(array $regRule, $routerString)
    {
        $extName = $this->_formatExt($regRule['ext']);
        $checkResult = $this->_checkUrl($extName, $routerString);
        if (!$checkResult)
        {
            return FALSE;
        }
        $this->_extName = $extName;
        list($controllerName, $actionName, $paramText) = $checkResult;
        if ($controllerName[0] == "/" || $controllerName[0] == "\\")
        {
            $controllerName = substr($controllerName, 1);
        }
        $params = [];
        if($paramText)
        {
            $paramList = explode('-', $paramText);
            for ($i = 1; $i< count($paramList); $i++)
            {
                $params[$paramList[$i]] = $paramList[$i + 1];
                $i++;
            }
        }
        if($controllerName)
        {
            $params['c'] = $controllerName;
        }
        if($actionName)
        {
            $params['a'] = $actionName;
        }
        
        $this->_params = $params;
        return TRUE;
    }

    
    /**
     * 获取路由解析后的URL参数
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }
    
    /**
     * 格式化扩展名
     * 
     * @param string $ext
     * 
     * @return string[]|mixed[]
     */
    protected function _formatExt($ext)
    {
        $ename = str_replace([' ', '.'], '', (string)$ext);
        if(!$ename)
        {
            $ename = self::DEFAULT_EXT;
        }
        return '.' . $ename;
    }
    
    /**
     * 检测URL是否符合路由规则
     *
     * @access protected
     * @param string $ext
     *        扩展名
     * @param string $routerString
     *        路由字符串
     * @return mixed
     *
     */
    protected function _checkUrl($ext, $routerString)
    {
        if ($index = strpos($routerString, "?"))
        {
            $routerString = substr($routerString, 0, $index);
        }
        $out = [];
        if (!preg_match(self::PATHINFO_PATTERN, $routerString, $out))
        {
            return FALSE;
        }
        if(!$out[2])
        {
            $cparams = explode('/', $out[1]);
            $actionName = array_pop($cparams);
            $controllerName = join('/', $cparams);
            return [$controllerName, $actionName];
        }
        
        // 检测扩展名
        if($out[4] && $out[4] != $ext)
        {
            return FALSE;
        }
        return array_slice($out, 1, 3);
    }
    
    /**
     * 根据参数重写并返回URL
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\Router\IRouter::rewriteUri()
     */
    public function rewriteUri($controllerName, $actionName, array $params)
    {
        if(!$params)
        {
            return $controllerName . '/' . $actionName;
        }
        $uris = [];
        foreach($params as $k => $v)
        {
            $uris[] = rawurlencode($k) . '-' . rawurlencode($v);
        }
        $uri = join('-', $uris);
        return '/' . $controllerName . '/' . $actionName . ($uris  ? '-' . $uri . $this->_extName : '');
    }
}
?>