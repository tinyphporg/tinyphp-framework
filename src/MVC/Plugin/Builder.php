<?php
/**
 * @Copyright (C), 2013-, King.
 * @Name Builder.php
 * @Author King
 * @Version Beta 1.0
 * @Date 2020年6月1日下午5:37:07
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年6月1日下午5:37:07 第一次建立该文件
 *                 King 2020年6月1日下午5:37:07 修改
 *
 */
namespace Tiny\MVC\Plugin;


use Tiny\MVC\ApplicationBase;
use Tiny\Config\Configuration;

/**
 * 打包器插件
 *
 * @package Tiny.MVC.Plugin
 * @since 2020年6月1日下午5:37:30
 * @final 2020年6月1日下午5:37:30
 */
class Builder implements Iplugin
{

    /**
     * 当前应用实例
     *
     * @var \Tiny\MVC\ApplicationBase
     */
    protected $_app;

    /**
     * app属性
     * @var Configuration
     */
    protected $_properties;

    /**
     * 初始化
     *
     * @param $app ApplicationBase
     *        当前应用实例
     * @return void
     */
    public function __construct(ApplicationBase $app)
    {
        $this->_app = $app;
        $this->_properties = $app->properties;
    }

    /**
     * 本次请求初始化时发生的事件
     *
     * @return void
     */
    public function onBeginRequest()
    {
    }

    /**
     * 本次请求初始化结束时发生的事件
     *
     * @return void
     */
    public function onEndRequest()
    {
    }

    /**
     * 执行路由前发生的事件
     *
     * @return void
     */
    public function onRouterStartup()
    {
        $config = $this->_app->properties['build'];
        if(!$config['enable'])
        {
            return;
        }
        $paramName = (string)$config['param_name'] ?: 'build';
        if (!$this->_app->request->param[$paramName])
        {
            return;
        }
        $bpath = $this->_app->properties['build.path'];
        if(!file_exists($bpath))
        {
            return;
        }

        $options = [];
        if (file_exists($config['setting_path']))
        {
            $options['setting_path'] = $config['setting_path'];
        }
        $options['application_path'] = $this->_app->path;
        $options['properties']  = (new Configuration($this->_app->profile))->get();
        $options['config'] = $this->_app->getConfig()->get();

        $options['attachments'][] = ['runtime', $this->_app->path . $this->_properties['app.runtime'], TRUE];

        //自定义config数据
        $spath = $this->_app->properties['build.setting_path'];
        if ($spath && file_exists($spath))
        {
            $options['attachments']['setting'] = ['setting', $spath];
        }

        //自定义properties
        $ppath = $this->_app->properties['build.profile_path'];
        if ($ppath && file_exists($ppath))
        {
            $options['attachments']['profile'] = ['profile', $ppath];
        }

        //框架路径
        $options['framework_path'] = FRAMEWORK_PATH;
        foreach ($this->_properties['imports'] as $ns => $path)
        {
            $options['imports'][$ns] = $this->_properties[$path];
        }

        //配置数据
        $bconfig = (new Configuration($bpath))->get();
        foreach($bconfig as $boption)
        {
            $buildOptions = $this->_formatOptions($options, $boption);
            if(!$buildOptions)
            {
                continue;
            }
            echo $buildOptions['name'] . " build starting\n";
            $ret = (new \Tiny\Build\Builder($buildOptions))->run();
            echo $buildOptions['name'] . " build ";
            echo $ret ? 'success' : 'faild';
            echo "\n";
            $this->_app->response->end();
        }
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
        if (is_array($boption['imports']))
        {
            foreach ($boption['imports'] as $ns => $path)
            {
                $boption['imports'][$ns] = $path;
            }
        }
        return $boption;
    }
    /**
     * 执行路由后发生的事件
     *
     * @return void
     */
    public function onRouterShutdown()
    {
    }

    /**
     * 执行分发前发生的动作
     *
     * @return void
     */
    public function onPreDispatch()
    {
    }

    /**
     * 执行分发后发生的动作
     *
     * @return void
     */
    public function onPostDispatch()
    {
    }
}
?>