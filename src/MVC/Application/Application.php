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
use Tiny\DI\Defintion;
use Tiny\Cache\Cache;
use Tiny\DI\FactoryDefintion;
use Tiny\DI\FactoryDefinition;
use Tiny\DI\ContainerInterface;
use Tiny\DI\Definition;
use Tiny\DI\DefintionProivder;

/**
 * application属性
 *
 * @package Tiny.MVC.Application
 * @since 2021年11月27日 下午1:01:32
 * @final 2021年11月27日下午1:01:32
 */
class Properties extends Configuration implements DefinitionProviderInterface
{
    
    /**
     * 注解链
     * 
     * @var array
     */
    protected $definitionProviderChain = [];
    
    
    public function __construct($cpath, ApplicationBase $app)
    {
        parent::__construct($cpath);
        $this->_app = $app;
      
        $this->initPath();
        $this->initDebug();
       // $this->definitionProviderChain[] = new DefintionProivder($this['container.config_path']);
    }

    /**
     * 增加注解实例
     * 
     * @param Defintion $defintion
     */
    public function addDefintion(Defintion $defintion)
    {
        
    }
    
    public function getDefinition($name)
    {
       $callable =  $this->createDefinition($name);
       if ($callable)
       {
           return new Definition($name, $callable);
       }
    }

    public function getDefinitions(): array
    {
        //return new s(function(){});
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
    
    protected function createDefinition($name)
    {
        switch ($name)
        {
            case 'cache' :
                return [$this, 'getcachedefinition'];
        }
        return false;
    }
    /**
     * 
     * @return \Tiny\DI\FactoryDefinition
     */
    public function getCacheDefinition()
    {
       // return new FactoryDefinition('cache', function(Properties $prop, ApplicationBase $a = null){
            
            return Cache::getInstance();
      //  });
    }
}

?>