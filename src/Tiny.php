<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Tiny.php
 * @author King
 * @version Beta 1.0 @Date: 2013-11-11上午04:43:47
 *          stable 1.0.01 2020年02月19日下午15:44:00
 * @Description 框架主体入口
 * @Class List 1.Registy 注册仓库 2.
 * @Function List 1.
 * @History King 2013-11-11上午04:43:47 0 第一次建立该文件
 *          King 2017-03-06上午修改
 *          King 2020年02月19日下午15:44:00 修改
 *          King 2020年6月1日14:21 stable 1.0.1 审定
 */
namespace Tiny;

use Tiny\Runtime\Runtime;
use Tiny\Runtime\Environment;
use Tiny\MVC\Application\ApplicationBase;

// 引入运行时类
require_once  __DIR__ . '/Runtime/Runtime.php';

/**
 * 工具类
 * 
 * 1.根据runtime的mode web|cli|rpc 创建application实例
 * 2.设置runtime的环境参数
 *
 * @package Tiny
 * @since 2019年11月12日上午10:11:04
 * @final 2020年02月19日下午15:44:00
 */
class Tiny
{
    
    /**
     * 当前的Runtime实例
     *
     * @var Runtime
     */
    protected static $runtime;
    
    /**
     * 注册或者替换已有的Application实例类
     *
     * @param int $runtimeMmode 运行环境模式 web|console|rpc
     * @param string $applicationClass 继承了ApplicationBase的application类
     */
    public static function registerApplicationClass(string $runtimeMmode, string $applicationClass)
    {
        return Runtime::registerApplicationClass($runtimeMmode, $applicationClass);
    }
    
    /**
     * 设置当前的Application实例
     *
     * @param ApplicationBase $app 继承了applicationBase的应用实例
     * @return mixed
     */
    public static function setApplication(ApplicationBase $app)
    {
        return Runtime::getInstance()->setApplication($app);
    }
    
    /**
     * 获取当前的application实例
     *
     * @return ApplicationBase
     */
    public static function getApplication()
    {
        return Runtime::getInstance()->getApplication();
    }
    
    /**
     * 根据运行环境的模式创建APP实例
     *
     * @param string $appPath application的工作目录
     * @param string $profile application的配置文件路径 默认为apppath目录下的config/profile.php.$this
     *        array 为数组时可设置多个配置文件路径
     * @return ApplicationBase
     */
    public static function createApplication(string $appPath, $profile = null)
    {
        return Runtime::getInstance()->createApplication($appPath, $profile);
    }
    
    /**
     * 设置Runtime的默认环境参数 仅在RunTime第一次实例化前有效
     *
     * @param array $env 运行时环境参数数组
     */
    public static function setENV(array $env)
    {
        return Environment::setEnv($env);
    }
}
?>