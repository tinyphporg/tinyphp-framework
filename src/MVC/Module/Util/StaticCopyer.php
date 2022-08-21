<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name StaticCopyer.php
 * @author King
 * @version stable 2.0
 * @Date 2022年8月16日下午7:06:17
 * @Class List class
 * @Function List function_container
 * @History King 2022年8月16日下午7:06:17 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Module\Util;

use Tiny\MVC\Module\ModuleException;

/**
 * 静态资源拷贝器
 *
 * @package Tiny.MVC.Module.Util
 * @since 2022年8月16日下午10:42:34
 * @final 2022年8月16日下午10:42:34
 */
class StaticCopyer
{
    
    /**
     * 复制文件夹去安装路径
     *
     * @param string $sourcePath 源文件路径
     * @param string $installPath 安装路径
     * @throws ModuleException::
     * @return void|boolean
     */
    public function copyto($sourcePath, $toPath, $exclude = false, $replace = false)
    {
        if (!is_dir($sourcePath)) {
            return false;
        }
        if (preg_match("/^(|\*|\/|\/(usr|home|root|lib|lib64|etc|var)\/?|)$/i", $toPath)) {
            return;
        }
        
        if (file_exists($toPath) && !is_dir($toPath)) {
            throw new ModuleException(sprintf('%s is a file!', $toPath));
        }
        if (!file_exists($toPath)) {
            mkdir($toPath, 0777, true);
        }
        
        $files = scandir($sourcePath);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $filename = $sourcePath . '/' . $file;
            $tofilename = $toPath . '/' . $file;
            
            if (is_dir($filename)) {
                $this->copyto($filename, $tofilename, $exclude, $replace);
                continue;
            }
            
            // 更新最新文件
            if (is_file($tofilename) && filemtime($tofilename) >= filemtime($filename)) {
                continue;
            }
            if ($exclude && preg_match($exclude, $filename)) {
                continue;
            }
            
            if (is_array($replace) && $this->replace($filename, $tofilename, $replace)) {
                continue;
            }
            $ret = copy($filename, $tofilename);
            if (!$ret) {
                throw new ModuleException(sprintf('copy failed: %s to %s', $filename, $tofilename));
            }
        }
    }
    
    /**
     * 替换字符串
     *
     * @param string $filename
     * @param string $tofilename
     * @param string $rconfig
     * @return  void
     */
    protected function replace($filename, $tofilename, $rconfig)
    {
        $replaceArr = [];
        foreach ($rconfig as $config) {
            
            // echo preg_match($config['regex'], $filename);
            if (!$config || !$config['regex'] || !preg_match($config['regex'], $filename)) {
                continue;
            }
            $source = (string)$config['source'];
            if (!$source) {
                continue;
            }
            
            $replace = trim((string)$config['replace']);
            if (!$replace) {
                continue;
            }
            $replaceArr[$source] = $replace;
        }
        
        if (!$replaceArr) {
            return;
        }
        $content = file_get_contents($filename);
        $content = strtr($content, $replaceArr);
        $ret = file_put_contents($tofilename, $content, LOCK_EX);
        return $ret !== false;
    }
}
?>