<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年3月13日上午12:16:34
 * @Class List
 * @Function List
 * @History King 2017年3月13日上午12:16:34 0 第一次建立该文件
 *          King 2017年3月13日上午12:16:34 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\View;

/**
 * 视图基类
 *
 * @package Tiny.Application.Viewer
 * @since 2017年3月12日下午3:29:18
 * @final 2017年3月12日下午3:29:18
 */
abstract class ViewEngine implements ViewEngineInterface
{
    
    /**
     * 支持的扩展名数组
     *
     * @var array
     */
    protected $extendNames = [];
    
    /**
     * 视图引擎的在应用缓存的key
     *
     * @var string
     */
    protected const CACHE_KEY = 'app.view.viewengine';
    
    /**
     * 当前的View对象
     *
     * @autowired
     * @var \Tiny\MVC\View\View
     */
    protected $view;
    
    /**
     * 应用缓存
     *
     * @autowired
     * @var \Tiny\Runtime\RuntimeCache
     */
    protected $cache;
    
    /**
     * 缓存的模板数据列表
     *
     * @var boolean
     */
    protected $templateData = false;
    
    /**
     * 视图引擎配置
     *
     * @autowired
     * @var array
     */
    protected $config = [];
    
    /**
     * 模板目录
     *
     * @var string
     */
    protected $templateDir;
    
    /**
     * 模板解析目录
     *
     * @var string
     */
    protected $compileDir;
    
    /**
     * 预先分配变量
     *
     * @var array
     */
    protected $variables = [];
    
    /**
     * 正在解析时的templateId
     *
     * @var string|bool
     */
    protected $fetchingTemplateId;
    
    /**
     * 正在解析时的函数变量名
     *
     * @var array
     */
    protected $fetchingVariables = [];
    
    /**
     * 增加匹配的扩展名
     *
     * @param string|array $extendName
     */
    public function addExtendName($extendName)
    {
        if (is_array($extendName)) {
            $this->extendNames = array_merge($this->extendNames, $extendName);
        } elseif(is_string($extendName)) {
            if (!in_array($extendName, $this->extendNames)) {
                $this->extendNames[] = $extendName;
            }
        }
        return false;
    }
    
    /**
     * 是否匹配对应的扩展名
     *
     * @param string $extendName 扩展名
     *       
     * @return boolean true 匹配|false 不匹配
     */
    public function matchExtendName(string $extendName)
    {
        return in_array($extendName, $this->extendNames);
    }
    
    /**
     * 设置模板引擎的模板文件夹
     *
     * @param string $path 文件夹路径
     * @return void
     */
    public function setTemplateDir($path)
    {
        $this->templateDir = $path;
    }
    
    /**
     * 获取模板引擎的模板文件夹
     *
     * @param string $path 文件夹路径
     * @return string 视图文件夹路径
     */
    public function getTemplateDir()
    {
        return $this->templateDir;
    }
    
    /**
     * 设置模板引擎的编译文件夹
     *
     * @param string $path 文件夹路径
     * @return void
     */
    public function setCompileDir($path)
    {
        $this->compileDir = $path;
    }
    
    /**
     * 获取模板引擎的编译文件夹
     *
     * @param string $path 文件夹路径
     * @return string 编译存放路径
     */
    public function getCompileFolder()
    {
        return $this->compileFolder;
    }
    
    /**
     * 分配变量
     *
     * @param string $key 变量分配的键
     * @param mixed $value 分配的值
     *       
     */
    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->variables = array_merge($this->variables, $key);
            return;
        }
    }
    
    /**
     * 获取输出的HTML内容
     *
     * @return string
     */
    public function fetch($tpath, array $assigns = [], $templateId = null)
    {
        $this->fetchingVariables = $assigns;
        $this->fetchingTemplateId = $templateId;
        $variables = $assigns ? array_merge($this->variables, $assigns) : $this->variables;
        $compileFile = $this->getCompiledFile($tpath, $templateId, $variables);
        return $this->fetchCompiledContent($compileFile, $variables);
    }
    
    /**
     * 通过模板路径获取模板编译文件
     *
     * @param string $tpath
     * @param boolean $isAbsolute
     */
    abstract public function getCompiledFile($tpath, $templateId = null);
    
    /**
     * 通过模板文件的真实路径获取文件内容
     *
     * @param string $tfile
     * @param mixed $assign
     * @return string
     */
    protected function fetchCompiledContent($compileFile, array $variables = [])
    {
        if ($variables) {
            extract($variables, EXTR_SKIP);
        }
        ob_start();
        include ($compileFile);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    /**
     * 获取template真实路径
     *
     * @param string $tpath
     * @param boolean $isAbsolute
     * @return mixed
     */
    protected function getTemplateRealPath($tpath, $templateId = null)
    {
        $pathinfo = $this->getTemplateRealPathinfoFromCache($tpath, $templateId);
        if ($pathinfo) {
            $this->view->addParsedTemplate($tpath, $pathinfo['path'], $this);
            return $pathinfo;
        }
        $pathinfo = $this->getTemplateRealPathinfo($tpath, $templateId);
        if (!$pathinfo) {
            return false;
        }
        
        $this->view->addParsedTemplate($tpath, $pathinfo['path'], $this);
        $this->saveToTemplateCache($tpath, $templateId, $pathinfo);
        return $pathinfo;
    }
    
    /**
     *
     * @param string $tpath
     * @param mixed $templateId
     * @return false|array
     */
    protected function getTemplateRealPathinfo($tpath, $templateId)
    {
        if (true === $templateId) {
            return $this->getPathinfo($tpath, $tpath);
        }
        $templateDirs = (is_array($this->templateDir)) ? $this->templateDir : [
            (string)$this->templateDir
        ];
        if (key_exists($templateId, $templateDirs)) {
            $tfile = $templateDirs[$templateId] . $tpath;
            if ($pathinfo = $this->getPathinfo($tfile, $tpath)) {
                return $pathinfo;
            }
        }
        
        foreach ($templateDirs as $tid => $tdir) {
            if (!is_int($tid)) {
                continue;
            }
            $tfile = $tdir . $tpath;
            if ($pathinfo = $this->getPathinfo($tfile, $tpath)) {
                return $pathinfo;
            }
        }
    }
    
    /**
     * 获取文件的路径信息
     *
     * @param string $tfile
     * @return boolean|mixed
     */
    protected function getPathinfo($tfile, $tpath)
    {
        if (!is_file($tfile)) {
            return false;
        }
        $pathinfo = pathinfo($tfile);
        $pathinfo['size'] = filesize($tfile);
        $pathinfo['mtime'] = filemtime($tfile);
        $pathinfo['path'] = $tfile;
        return $pathinfo;
    }
    
    /**
     * 从缓存里获取模板的真实文件信息
     *
     * @param string $tpath
     * @param string $templateId
     * @return boolean|array
     */
    protected function getTemplateRealPathinfoFromCache($tpath, $templateId)
    {
        $templateKey = $this->getTemplateCacheKey($templateId);
        $templateData = $this->templateData;
        if (!$templateData || !key_exists($templateKey, $templateData)) {
            return false;
        }
        
        $templatePaths = (array)$templateData[$templateKey];
        if (!key_exists($tpath, $templatePaths)) {
            return false;
        }
        
        $pathinfo = (array)$templatePaths[$tpath];
        if (!$pathinfo) {
            return false;
        }
        return $pathinfo;
    }
    
    /**
     * 设置模板视图缓存并保存
     *
     * @param string $templateKey 模板ID生成的KEY
     * @param string $tpath 模板路径
     * @param array $pathinfo 模板路径的路径信息数组
     */
    protected function saveToTemplateCache($tpath, $templateId, array $pathinfo)
    {
        $templateKey = $this->getTemplateCacheKey($templateId);
        $this->templateData[$templateKey][$tpath] = $pathinfo;
        $this->cache->set(self::CACHE_KEY, $this->templateData);
    }
    
    /**
     * 根据模板ID生成模板缓存的key
     *
     * @param mixed $templateId
     * @return string
     */
    protected function getTemplateCacheKey($templateId)
    {
        if (false == $this->templateData) {
            $this->templateData = (array)$this->cache->get(self::CACHE_KEY);
        }
        if (true === $templateId) {
            return '__tinyphp__true';
        }
        if (null == $templateId) {
            return '__tinyphp_null';
        }
        return (string)$templateId;
    }
}
?>