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
 *
 */
namespace Tiny;

use Tiny\Runtime\Runtime;
use Tiny\Runtime\Environment;
use Tiny\MVC\ApplicationBase;

/* 加载必须的程序运行时(Runtime)对象 */
require_once __DIR__ . '/Runtime/Runtime.php';

/**
 * 工具类
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
     * 注册或者替换已有的Application实例类
     *
     * @param int $mode
     *        运行模式
     * @param string $className
     *        ApplicationBase实例名
     * @return void
     */
    public static function regApplicationMap($mode, $className)
    {
        return Runtime::regApplicationMap($mode, $className);
    }

    /**
     * 设置当前的Application实例
     *
     * @param ApplicationBase $app
     *        实现了applicationBase的应用实例
     * @return void
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
     * 自动根据运行环境创建APP
     *
     * @param string $appPath
     *        application工作目录
     * @param string $profile
     *        配置文件路径 默认为apppath目录下的config/profile.php
     * @param array $env
     *        runtime环境参数 仅在第一次调用此函数时设置有效
     * @return ApplicationBase
     */
    public static function createApplication($appPath, $profile = NULL)
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