<?php
/**
 * @Copyright (C), 2013-, King.
 * @Name WorkerHandlerInterface.php
 * @Author King
 * @Version Beta 1.0
 * @Date 2020年4月10日下午8:03:55
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年4月10日下午8:03:55 第一次建立该文件
 *                 King 2020年4月10日下午8:03:55 修改
 *
 */
namespace Tiny\Console\Worker;

/**
 * Worker委托事件句柄接口
 *
 * @package Tiny.Console.Worker
 * @since 2020年6月1日下午2:24:20
 * @final 2020年6月1日下午2:24:20
 */
interface WorkerHandlerInterface
{
    /**
     * 进程的执行接口
     * @param string $controller 控制器名
     * @param string $method 控制器的成员函数
     * @param array $args 参数数组
     * @param $isMethod 是否为单纯的成员函数，否则为动作函数
     */
    public function onWorkerDispatch($controller, $method, array $args = [], bool $isMethod = true);
}
?>