<?php 
/**
 *
 * @copyright (C), 2013-, King.
 * @name BuilderEventListener.php
 * @author King
 * @version stable 2.0
 * @Date 2022年1月19日下午10:39:45
 * @Class List class
 * @Function List function_container
 * @History King 2022年1月19日下午10:39:45 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\MVC\Event\Plugin;

use Tiny\Config\Configuration;
use Tiny\MVC\Application\ApplicationBase;
use Tiny\MVC\Application\Properties;
use Tiny\MVC\Response\Response;
use Tiny\MVC\Request\Request;
use Tiny\MVC\Event\MvcEvent;
use Tiny\MVC\Application\ConsoleApplication;
use Tiny\MVC\Event\Listener\RouteEventListener;

/**
*  打包器监听插件
*  
* @package Tiny.MVC.Event.Listener
* @since 2022年2月17日上午11:43:39
* @final 2022年2月17日上午11:43:39
*/
class Builder implements RouteEventListener
{
    /**
     * 当前应用实例
     * @var ConsoleApplication
     */
    protected $app;
    
    /**
     * 应用配置实例
     * 
     * @var Properties
     */
    protected $properties;
    
    /**
     * 当前应用的响应实例
     * 
     * @var Response
     */
    protected $response;
    
    /**
     * 当前应用的请求实例
     * 
     * @var Request
     */
    protected $request;
    
    /**
     * 构造函数
     * 
     * @param ApplicationBase $app
     */
    public function __construct(ApplicationBase $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->response = $app->response;
        $this->properties = $app->properties;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\Event\Listener\RouteEventListener::onRouterStartup()
     */
    public function onRouterStartup(MvcEvent $event, array $params)
    {
        $config = $this->properties['builder'];
        if(!$config['enabled'])
        {
            return;
        }
        
        $paramName = (string)$config['param_name'] ?: 'build';
        if (!$this->request->param[$paramName])
        {
            return;
        }
        
        $bpath = $this->properties['builder.path'];
        if(!file_exists($bpath))
        {
            return;
        }
        
        $options = [];
        if (file_exists($config['config_path']))
        {
            $options['config_path'] = $config['config_path'];
        }
        
        $options['application_path'] = $this->app->path;
        $options['properties']  = (new Configuration($this->app->profile))->get();
        $options['config'] = $this->app->getConfig()->get();
        $options['home_attachments']['runtime'] = ['runtime', $this->properties['src.runtime'], true];
        
        //自定义config数据
        $spath = $this->properties['builder.config_path'];
        if ($spath && file_exists($spath))
        {
            $options['home_attachments']['config'] = ['config', $spath];
        }
        
        //自定义properties
        $ppath = $this->properties['builder.profile_path'];
        if ($ppath && file_exists($ppath))
        {
            $options['home_attachments']['profile'] = ['profile', $ppath];
        }
        
        // namespace
        $options['namespaces'] = $this->properties['autoloader.namespaces'];
        $options['classes'] = $this->properties['autoloader.classes'];
        
        $options['php_path'] = $this->request->env['PHP_PATH'];
        //配置数据
        $bconfig = (new Configuration($bpath))->get();
        foreach($bconfig as $boption)
        {
            $buildOptions = $this->_formatOptions($options, $boption);
            if(!$buildOptions)
            {
                continue;
            }
            $this->build($buildOptions);
        }
        
        $this->app->response->end();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\Event\Listener\RouteEventListener::onRouterShutdown()
     */
    public function onRouterShutdown(MvcEvent $event, array $params)
    {
        
    }
    
    /**
     * 开始构建
     *
     * @param array $buildOptions
     */
    protected function build(array $buildOptions)
    {
        echo $buildOptions['name'] . " builder starting\n";
        
        $ret = (new \Tiny\Build\Builder($buildOptions))->run();
        echo $buildOptions['name'] . " builder ";
        echo $ret ? 'success' : 'faild';
        echo "\n";
    }
    
    /**
     * 格式化打包器的配置选项
     * @param array $options
     * @param array $boption
     * @return array
     */
    protected function _formatOptions($options, $boption)
    {
        $boption = array_merge($boption, $options);
        $boption['name'] = $boption['name'] ?: 'tinyd';
        $boption['exclude'] = is_array($boption['exclude']) ? $boption['exclude'] : [(string)$boption['exclude']];
        $boption['exclude'][] = "/\.phar$/";
        
        //框架路径
        $boption['framework_path'] = $boption['framework_path'] ?: TINY_FRAMEWORK_PATH;
        
        // vendor 路径
        $boption['vendor_path'] = $boption['vendor_path'] ?: dirname(dirname(get_included_files()[0])) . '/vendor';
        
        // imports
        if (is_array($boption['imports']))
        {
            foreach ($boption['imports'] as $ns => $path)
            {
                $boption['imports'][$ns] = $path;
            }
        }
        return $boption;
    }
}

?>