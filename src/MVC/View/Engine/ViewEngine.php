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

use Tiny\MVC\View\ViewException;
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
     * 当前的View对象
     *
     * @var View
     */
    protected $view;

    /**
     * 视图引擎配置
     *
     * @var array
     */
    protected $viewEngineConfig = [];

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
     * 是否缓存编译后的模板
     *
     * @var boolean
     */
    protected $cacheEnabled = false;

    /**
     * 模板缓存路径
     *
     * @var string
     */
    protected $cacheDir = '';

    /**
     * 模板缓存时间
     *
     * @var integer
     */
    protected $cacheTtl = 120;

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\ViewEngineInterface::setViewEngineConfig()
     */
    public function setViewEngineConfig(View $view, array $config)
    {
        $this->view = $view;
        $this->viewEngineConfig += $config;
    }

    /**
     * 设置模板引擎的模板文件夹
     *
     * @param string $path
     *            文件夹路径
     * @return void
     */
    public function setTemplateDir($path)
    {
        $this->templateDir = $path;
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
        return $this->templateDir;
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
        $this->compileDir = $path;
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
        return $this->compileFolder;
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
    public function assign($key, $value = null)
    {
        if (is_array($key))
        {
            $this->variables = array_merge($this->variables, $key);
            return;
        }
        $this->assign($key, $value);
    }

    /**
     * 获取输出的HTML内容
     *
     * @return string
     */
    public function fetch($tpath, $assign = false, $isAbsolute = false)
    {
        $compileFile = $this->getCompiledFile($tpath, $isAbsolute);
        return $this->fetchCompiledContent($compileFile, $assign);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\ViewEngineInterface::setCache()
     */
    public function setCache($cacheDir, int $cacheTtl= 120)
    {
        $this->cacheEnabled = ($cacheTtl <= 0) ? false : true;
        $this->cacheFolder = $cacheDir;
        if (!is_dir($cacheDir))
        {
            throw new ViewException('cachedir is not exists!');
        }
        $this->cacheTtl= $cacheTtl;
    }

    /**
     * 通过模板路径获取模板编译文件
     *
     * @param string $tpath
     * @param boolean $isAbsolute
     */
    abstract public function getCompiledFile($tpath, $isAbsolute = false);

    /**
     * 通过模板文件的真实路径获取文件内容
     *
     * @param string $tfile
     * @param mixed $assign
     * @return string
     */
    protected function fetchCompiledContent($compileFile, $assign = false)
    {
        $variables = is_array($assign) ? array_merge($this->variables, $assign) : $this->variables;
        ob_start();
        extract($variables, EXTR_SKIP);
        include $compileFile;
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
    protected function getTemplateRealPath($tpath, $isAbsolute = false)
    {
        if ($isAbsolute && is_file($tpath))
        {
            $this->view->addTemplateList($tpath, $tpath, $this);
            return $tpath;
        }

        if ($isAbsolute)
        {
            return false;
        }

        if (is_array($this->templateDir))
        {
            foreach ($this->templateDir as $tdir)
            {
                $tePath = $tdir . $tpath;
                if (is_file($tePath))
                {
                    $this->view->addTemplateList($tpath, $tePath, $this);
                    return $tePath;
                }
            }
            return false;
        }
        $tpath = $this->templateDir . $tpath;
        if (!is_file($tpath))
        {
            return false;
        }
        return $tpath;
    }
}
?>