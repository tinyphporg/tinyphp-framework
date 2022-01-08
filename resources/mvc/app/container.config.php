<?php


use Tiny\MVC\View\View;
use Tiny\MVC\Application\Properties;
use Tiny\MVC\ApplicationBase;
use Tiny\DI\Container;
use const Tiny\MVC\TINY_MVC_RESOURCES;
use Tiny\Cache\Cache;
//use App\Model\Main\UserInfo;

return [
    Cache::class => function(ApplicationBase $app) {
        
        $config = $app->properties['cache'];
        if (! $config['enabled'])
        {
            return null;
        }
        
        // 缓存自定义适配器
        $adapters = (array)$config['storage']['adapters'] ?: [];
        foreach ($adapters as $id => $className)
        {
            Cache::regStorageAdapter($id, $className);
        }
        
        $ttl = (int)$config['ttl'] ?: 60;
        $storageId = (string)$config['storage']['id'] ?: 'default';
        $storagePath = (string)$config['storage']['path'];
        
        $cacheInstance = new Cache();
        $cacheInstance->setDefaultStorageId($storageId);
        $cacheInstance->setDefaultStoragePath($storagePath);
        $cacheInstance->setDefaultTtl($ttl);
        
        $storageConfig = ($config['storage']['config']) ?: [];
        foreach ($storageConfig as $scfg)
        {
            $options = (array)$scfg['options'] ?: [];
            $cacheInstance->addStorageAdapter($scfg['id'], $scfg['storage'], $options);
        }
        return $cacheInstance;
},
    View::class => function(ApplicationBase $app, Container $container) {
        $viewInstance = new \Tiny\MVC\View\View($app);
        $config = $app->properties['view'];
        
        $helpers = (array)$config['helpers'];
        $engines = (array)$config['engines'];
        
        $assign = (array)$config['assign'] ?: [];
        if ($app->properties['config.enabled'])
        {
            $assign['config'] = $container->get('config');
        }
        $defaultTemplateDirname = TINY_MVC_RESOURCES . 'views/';
        $templateDirs = [$defaultTemplateDirname];
        $templateDirname = $config['template_dirname'] ?: 'default';
        $templateDirs[] = $config['src'] . $templateDirname . DIRECTORY_SEPARATOR;
        
        // composer require tinyphp-ui;
        $uiconfig = $config['ui'];
        if ($uiconfig['enabled'])
        {
            $uiHelperName = (string)$uiconfig['helper'];
            if ($uiHelperName)
            {
                $helpers[] = [ 'helper' => $uiHelperName];
            }
            $templatePlugin = (string)$uiconfig['template_plugin'];
            if($templatePlugin)
            {
                $uiPluginConfig = [
                    'public_path' => $config['ui']['public_path'],
                    'inject' => $config['ui']['inject'],
                    'dev_enabled' => $config['ui']['dev_enabled'],
                    'dev_public_path' => $config['ui']['dev_public_path']
                ];
                $engines[] = ['engine' => '\Tiny\MVC\View\Engine\Template', 'config' => ['plugins' => [['plugin' => $templatePlugin, 'config' => $uiPluginConfig]]] ];
            }
            if ($uiconfig['template_dirname'])
            {
                $templateDirs[] = (string)$uiconfig['template_dirname'];
            }
        }
        
        if ($this->properties['lang.enabled'])
        {
            $assign['lang'] = $container->get('lang');
            if ($config['view']['lang']['enabled'] !== FALSE)
            {
                $templateDirs[] = $config['src'] . $this->_prop['lang']['locale'] . DIRECTORY_SEPARATOR;
            }
        }
        
        // 设置模板搜索目录
        $templateDirs = array_reverse($templateDirs);
        $viewInstance->setTemplateDir($templateDirs);
        if ($config['cache'] && $config['cache']['enabled'])
        {
            $viewInstance->setCache($config['cache']['dir'], (int)$config['cache']['lifetime']);
        }
        
        // engine初始化
        foreach ($engines as $econfig)
        {
            $viewInstance->bindEngine($econfig);
        }
        
        //helper初始化
        foreach ($helpers as $econfig)
        {
            $viewInstance->bindHelper($econfig);
        }
        
        $viewInstance->setCompileDir($config['compile']);
        
        $viewInstance->assign($assign);
        return $viewInstance;
    }
]
?>