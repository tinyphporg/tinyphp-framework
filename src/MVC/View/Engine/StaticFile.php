<?php
/**
 * 静态模板解析类
 * 
 * @copyright (C), 2013-, King.
 * @name Static.php
 * @author King
 * @version stable 2.0
 * @Date 2022年6月10日上午10:27:47
 * @Class List class
 * @Function List function_container
 * @History King 2022年6月10日上午10:27:47 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\View\Engine;

use Tiny\MVC\View\ViewException;

/**
 *
 * @package namespace
 * @since 2022年6月10日上午10:30:25
 * @final 2022年6月10日上午10:30:25
 */
class StaticFile extends ViewEngine
{
    /**
     * 支持匹配解析的扩展名文件
     * 
     * @var array
     */
    protected $extendNames = ['js', 'css', 'jpg', 'gif', 'png'];
    
    protected $minsize = 2048;
    
    protected $basedir;
    
    protected $publicPath;
    
    protected $pathinfos = [];
    
    /**
     * 初始化构造函数
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (!$config['basedir']) {
            throw new ViewException('basedir is not set');
        }
        if (!$config['public_path']) {
            throw new ViewException('public is not set');
        }
        
        $this->basedir = $config['basedir'] . 'staticfile/';
        $this->publicPath = $config['public_path'] . 'staticfile/';
        $this->minsize = (int)$config['minsize'] ?: 2048;
    }
    
    /**
     * \
     *
     * {@inheritdoc}
     * @see \Tiny\MVC\View\Engine\ViewEngine::getCompiledFile()
     */
    public function getCompiledFile($tpath, $templateId = null)
    {
        $pathinfo = $this->getTemplateRealPath($tpath, $templateId);
        
        if (!$pathinfo) {
            return false;
        }
        $tfile = $pathinfo['path'];
        $tfilemtime = $this->app->isDebug ? filemtime($tfile) : $pathinfo['mtime'];
        
        // 如果开启模板缓存 并且 模板存在且没有更改
        $compilePath = $this->createCompileFilePath($tfile);
        if (((extension_loaded('opcache') && opcache_is_script_cached($compilePath)) || file_exists($compilePath)) && (filemtime($compilePath) > $tfilemtime)) {
            return $compilePath;
        }
        
        $compileContent = $this->parseTemplateFile($pathinfo);
        file_put_contents($compilePath, $compileContent, LOCK_EX);
        return $compilePath;
    }
    
    /**
     * 解析模板文件
     *
     * @param array $pathinfo 模板的路径信息数组
     * @throws ViewException
     * @return string
     */
    protected function parseTemplateFile($pathinfo)
    {
        $tfile = $pathinfo['path'];
        $size = $pathinfo['size'];
        $ext = $pathinfo['extension'];
        
        $iscopyto = true;
        // @formatter:off
        if ($size <= $this->minsize && in_array($ext, ['css', 'js'])) {
            $iscopyto = true;
        }
        // @formatter:on
        
        if (!$iscopyto) {
            $fh = fopen($tfile, 'rb');
            if (!$fh) {
                throw new ViewException("viewer error: fopen $tfile is faild");
            }
            flock($fh, LOCK_SH);
            $filesize = filesize($tfile);
            $template = $filesize > 0 ? fread($fh, $filesize) : '';
            flock($fh, LOCK_UN);
            fclose($fh);
            return $this->parseJavascriptAndCss($template, $ext);
        }
        
        if (file_exists($this->basedir) && !is_dir($this->basedir)) {
            throw new ViewException(sprintf("viewer staticfile: basedir %s must be a dir!", $this->basedir));
        }
        if (!file_exists($this->basedir)) {
            mkdir($this->basedir, 0777);
        }
        
        $sourcepath = $pathinfo['path'];
        $filename = $pathinfo['filename'] . '.' . substr(md5($sourcepath), -8) . '.' . $pathinfo['extension'];
        $topath = $this->basedir . $filename;
        if (!copy($sourcepath, $topath)) {
            throw new ViewException(sprintf("viewer staticfile: copy %s  to %s is error!", $sourcepath, $topath));
        }
        
        $toPublicpath = $this->publicPath . $filename;
        switch ($ext) {
            case 'js':
                return sprintf('<script src="%s" type="text/javascript"></script>', $toPublicpath);
            case 'css':
                return sprintf('<link href="%s" rel="stylesheet"/>', $toPublicpath);
        }
        // @formatter:off
        if (in_array($ext, ['jpeg', 'jpg', 'gif', 'png', 'icon', 'ico', 'bmp'])) {
            return sprintf('<img src="%s" rel="stylesheet"/>', $toPublicpath);
        }
        // @formatter:on
        
        return $toPublicpath;
    }
    
    /**
     * 生成一个编译模板文件的文件名
     *
     * @param string $tfile 输入的编译模板路径
     * @return string
     */
    protected function createCompileFilePath($tfile)
    {
        return $this->compileDir . md5($tfile) . '.static.php';
    }
    
    /**
     * 解析js和css脚本
     *
     * @param array $pathinfo
     * @param boolean $isCopyto
     * @return string
     */
    protected function parseJavascriptAndCss($template, $ext)
    {
        if ('css' == $ext) {
            return "<style>" . $template . '</style>';
        }
        return '<script type="text/javascript">' . $template . '</script>';
    }
}
?>