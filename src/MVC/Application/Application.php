<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Application.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月26日下午6:38:24 0 第一次建立该文件
 *          King 2021年11月26日下午6:38:24 1 修改
 *          King 2021年11月26日下午6:38:24 stable 1.0.01 审定
 */
namespace Tiny\MVC\Application;

use Tiny\Config\Configuration;
use Tiny\DI\DefinitionProviderInterface;
use Tiny\MVC\ApplicationBase;

/**
 * application属性
 *
 * @package Tiny.MVC.Application
 * @since 2021年11月27日 下午1:01:32
 * @final 2021年11月27日下午1:01:32
 */
class Properties extends Configuration implements DefinitionProviderInterface
{

    public function __construct($cpath, ApplicationBase $app)
    {
        parent::__construct($cpath);
        $this->_app = $app;
        $this->initPath();
        $this->initDebug();
    }

    public function getDefinition($name)
    {
    }

    public function getDefinitions(): array
    {
        return [];
    }

    protected function initPath()
    {
        $appPath = $this->_app->path;
        $paths = $this->get('path');
        $runtimePath = $this->get('app.runtime');

        if (! $runtimePath)
        {
            $runtimePath = $appPath . 'runtime/';
        }
        if ($runtimePath && 0 === strpos($runtimePath, 'runtime'))
        {
            $runtimePath = $appPath . $runtimePath;
        }

        foreach ($paths as $p)
        {
            $path = $this->get($p);
            if (! $path)
            {
                continue;
            }
            if (0 === strpos($path, 'runtime'))
            {
                $rpath = preg_replace("/\/+/", "/", $runtimePath . substr($path, 7));
                if (! file_exists($rpath))
                {
                    mkdir($rpath, 0777, TRUE);
                }
                $this->set($p, $rpath);
                continue;
            }
            $this->set($p, realpath($appPath . $path) . DIRECTORY_SEPARATOR);
        }
    }

    protected function initDebug()
    {
        $debugConfig = $this->get('debug'); 
        if ($debugConfig['enabled'] && $debugConfig['plugin'])
        {
            $this->set('plugins.debug',  $debugConfig['plugin']);
        }
    }
}

?>