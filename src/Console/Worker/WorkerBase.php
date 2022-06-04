<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Base.php
 * @author King
 * @version Beta 1.0
 * @Date 2020年4月6日上午11:00:39
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年4月6日上午11:00:39 第一次建立该文件
 *          King 2020年4月6日上午11:00:39 修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\Console\Worker;

/**
 * Worker基类
 *
 * @package Tiny.Console.Worker
 * @since 2020年6月1日下午2:25:05
 * @final 2020年6月1日下午2:25:05
 */
abstract class WorkerBase
{
    
    /**
     * worker进程ID
     *
     * @var integer
     */
    protected $pid = 0;
    
    /**
     * workerID
     *
     * @var integer
     */
    protected $id;
    
    /**
     * daemon pid
     *
     * @var int
     */
    protected $daemonPid;
    
    /**
     * Daemon pid file
     *
     * @var string
     */
    protected $daemonPidFile;
    
    /**
     * 默认开启的工作进程
     *
     * @var integer
     */
    protected $num = 1;
    
    /**
     * 策略数组
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * 派发器配置
     *
     * @var array
     */
    protected $dispatcher = [
        'controller' => 'main',
        'action' => 'index',
        'module' => '',
        'args' => [],
    ];
    
    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->pid = posix_getpid();
        if (!$this->formatConfig($config)) {
            throw new WorkerException(sprintf('Worker Exception: options：%s is format faild!', var_export($config, true)));
        }
    }
    
    /**
     * 获取worker的ID 进程间通讯
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * 获取worker的进程数
     *
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }
    
    /**
     * 设置守护进程的选型
     *
     * @param int $daemonPid
     * @param string $daemonPidFile
     */
    public function setDaemonOptions($daemonPid, $daemonPidFile)
    {
        $this->daemonPid = $daemonPid;
        $this->daemonPidFile = $daemonPidFile;
    }
    
    /**
     * 初始化 在成为子进程之前
     *
     * @return true
     */
    public function init()
    {
        if (!$this->preDispatch()) {
            throw new WorkerException('Worker Exception: options.dispatch is format faild!');
        }
        return true;
    }
    
    /**
     * 成为子进程后 开始
     *
     * @return boolean
     */
    public function start()
    {
        // init pid
        $this->pid = posix_getpid();
        return $this->dispatch('onstart');
    }
    
    /**
     * exit 退出后
     *
     * @return boolean
     */
    public function stop()
    {
        return $this->dispatch('onstop');
    }
    
    /**
     * worker正式运行
     */
    abstract public function run();
    
    /**
     * 执行前派发检测
     * 
     * @param string $method
     * @param bool $isMethod
     * @return mixed
     */
    protected function preDispatch(string $method = '', bool $isMethod = true)
    {
        $controllerName = $this->dispatcher['controller'];
        $actionName = $this->dispatcher['action'];
        $moduleName = $this->dispatcher['module'];
        if (!$method) {
            $method = $actionName;
            $isMethod = false;
        }
        $callback = $this->dispatcher['preCallback'];
        $params = [$controllerName, $method, $moduleName, $isMethod];
        return call_user_func_array($callback, $params);
    }
    /**
     * 派发
     *
     * @param string $method 函数名
     * @param string $args 函数参数数组
     */
    protected function dispatch(string $method = '', array $args = [], bool $isMethod = true)
    {
        $args = array_merge($args, $this->dispatcher['args']);
        $controllerName = $this->dispatcher['controller'];
        $actionName = $this->dispatcher['action'];
        $moduleName = $this->dispatcher['module'];     
        if (!$method) {
            $method = $actionName;
            $isMethod = false;
        }
        
        $callback = $this->dispatcher['callback'];
        $params = [$controllerName, $method, $moduleName, $args, $isMethod];
        
        //dispatch
        return call_user_func_array($callback, $params);

    }
    
    /**
     * 格式化选项数组
     *
     * @param array $config 配置数组
     * @throws WorkerException
     */
    protected function formatConfig(array $config)
    {
        $id = (string)$config['id'];
        if (!$id) {
            return;
        }
        $this->id = $id;
        
        // handler
        $handler = $config['handler'];
        if (!$handler || !$handler instanceof WorkerHandlerInterface) {
            return;
        }

        //dispatcher
        $dispatcher = (array)$config['dispatcher'];
        if (!$dispatcher) {
            return;
        }
        
        $dispatcher['args'] = (array)$dispatcher['args'];
        $dispatcher['callback'] = [$handler, 'onWorkerDispatch'];
        $dispatcher['preCallback'] = [$handler, 'onWorkerPreDispatch'];
        $this->dispatcher = array_merge($this->dispatcher, $dispatcher);
        
        // options
        $options = (array)$config['options'];
        $this->options = array_merge($options, $this->options);
        
        // worker num
        $this->num = (int)$config['num'];
        return true;
    }
    
    /**
     * 守护进程是否正常运行
     *
     * @return false|int
     */
    protected function daemonIsRunning()
    {
        if (!file_exists($this->daemonPidFile)) {
            return false;
        }
        $pid = (int)file_get_contents($this->daemonPidFile);
        if ($pid != $this->daemonPid) {
            return false;
        }
        return file_exists('/proc/' . $pid);
    }
}
?>