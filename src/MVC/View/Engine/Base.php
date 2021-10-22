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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\ViewException;

/**
 * 视图基类
 *
 * @package Tiny.Application.Viewer
 * @since 2017年3月12日下午3:29:18
 * @final 2017年3月12日下午3:29:18
 */
abstract class Base implements IEngine
{

    /**
     * 模板目录
     *
     * @var string
     */
    protected $_templateDir;

    /**
     * 模板解析目录
     *
     * @var string
     */
    protected $_compileDir;

    /**
     * 预先分配变量
     *
     * @var array
     */
    protected $_variables = [];

    /**
     * 是否缓存编译后的模板
     *
     * @var boolean
     */
    protected $_cacheEnabled = FALSE;

    /**
     * 模板缓存路径
     *
     * @var string
     */
    protected $_cacheDir = '';

    /**
     * 模板缓存时间
     *
     * @var integer
     */
    protected $_cacheLifetime = 120;

    /**
     * 设置模板引擎的模板文件夹
     *
     * @param string $path
     *            文件夹路径
     * @return void
     */
    public function setTemplateDir($path)
    {
        $this->_templateDir = $path;
    }

    /**
     * 获取模板引擎的模板文件夹
     *
     * @param string $path
     *            文件夹路径
     * @return string 视图文件夹路径
     */
    public function getTemplateDir()
    {
        return $this->_templateDir;
    }

    /**
     * 设置模板引擎的编译文件夹
     *
     * @param string $path
     *            文件夹路径
     * @return void
     */
    public function setCompileDir($path)
    {
        $this->_compileDir = $path;
    }

    /**
     * 获取模板引擎的编译文件夹
     *
     * @param string $path
     *            文件夹路径
     * @return string 编译存放路径
     */
    public function getCompileFolder()
    {
        return $this->_compileFolder;
    }

    /**
     * 分配变量
     *
     * @param string $key
     *            变量分配的键
     * @param mixed $value
     *            分配的值
     * @return void
     *
     */
    public function assign($key, $value = NULL)
    {
        if (is_array($key)) {
            $this->_variables = array_merge($this->_variables, $key);
            return;
        }
        $this->assign($key, $value);
    }

    /**
     * 获取输出的HTML内容
     *
     * @return string
     */
    public function fetch($tpath, $isAbsolute = FALSE)
    {
        $compileFile  = $this->getCompiledFile($tpath, $isAbsolute);
        return $this->_fetchCompiledContent($compileFile);
    }
    
    /**
     * 输出解析内容
     *
     * @param string $template
     *            模板路径
     * @return void
     *
     */
    public function display($tpath, $isAbsolute = FALSE)
    {
        $compileFile  = $this->getCompiledFile($tpath, $isAbsolute);
        $this->_displayCompiledContent($compileFile);
    }

    /**
     * 设置模板缓存
     * 
     * @see \Tiny\MVC\View\Engine\IEngine::setCache()
     */
    public function setCache($cacheDir, int $cacheLifetime = 120)
    {
        $this->_cacheEnabled = ($cacheLifetime <= 0) ? FALSE : TRUE;
        $this->_cacheFolder = $cacheDir;
        if (! is_dir($cacheDir)) {
            throw new ViewException('cachedir is not exists!');
        }
        $this->_cacheLifetime = $cacheLifetime;
    }
    
    /**
     * 通过模板路径获取模板编译文件
     * @param string $tpath
     * @param boolean $isAbsolute
     */
    abstract public function getCompiledFile($tpath, $isAbsolute = FALSE);
        
    /**
     * 通过模板文件的真实路径获取文件内容
     *
     * @param string $tfile
     * @return string
     */
    protected function _fetchCompiledContent($compileFile)
    {
        ob_start();
        extract($this->_variables, EXTR_SKIP);
        include $compileFile;
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    /**
     * 输出编译后的内容
     *
     * @param string $compileFile
     * @return void
     */
    protected function _displayCompiledContent($compileFile)
    {
        extract($this->_variables, EXTR_SKIP);
        include $compileFile; 
    }
    
    /**
     * 获取template真实路径
     * @param string $tpath
     * @param boolean $isAbsolute
     * @return mixed
     */
    protected function _getTemplateRealPath($tpath, $isAbsolute = FALSE)
    {
        if ($isAbsolute && is_file($tpath))
        {
            return $tpath;
        }
        
        if ($isAbsolute)
        {
            return FALSE;
        }
        
        if (is_array($this->_templateDir))
        {
            foreach($this->_templateDir as $tdir)
            {
                $tePath = $tdir . $tpath;
                if (is_file($tePath))
                {
                    return $tePath;
                }
            }
            return FALSE;
        }
        
        $tpath = $this->_templateDir . $tpath;
        if (!is_file($tpath))
        {
            return FALSE;
        }
        return $tpath;
    }
    
}
?>