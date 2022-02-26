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
abstract class Base
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
     * 执行worker委托的代理实例
     *
     * @var WorkerHandlerInterface
     */
    protected $handler;
    
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
        'args' => [],
    ];
    
    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->pid = posix_getpid();
        $ret = $this->formatConfig($config);
        if (!$ret) {
            throw new WorkerException(
                sprintf('Worker Excetion: options：%s is format faild!', var_export($config, true)));
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
     * 设置worker回调的handler实例
     *
     * @param WorkerHandlerInterface $whandler
     */
    public function setWorkerHandler(WorkerHandlerInterface $whandler)
    {
        $this->handler = $whandler;
    }
    
    /**
     * 获取设置的worker回调的handler实例
     *
     * @return mixed
     */
    public function getWorkerHandler()
    {
        return $this->handler;
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
     * @return boolean
     */
    public function init()
    {
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
     * 派发
     *
     * @param string $action
     */
    protected function dispatch(string $method = null, array $args = [])
    {
        if (!$this->handler) {
            return;
        }
        
        $isMethod = true;
        if (!$method) {
            $method = $this->dispatcher['action'];
            $args  = array_merge($args, (array)$this->dispatcher['args']);
            $isMethod = false;
        }
        return call_user_func_array($this->dispatcher['callback'],
            [
                $this->dispatcher['controller'],
                $method,
                $args,
                $isMethod
            ]);
    }
    
    /**
     * 格式化选项数组
     *
     * @param array $options
     * @throws WorkerException
     */
    protected function formatConfig(array $config)
    {
        if (!$config['id']) {
            return false;
        }
        $this->id = $config['id'];
        
        // handler
        if ($config['handler'] && $config['handler'] instanceof WorkerHandlerInterface) {
            $this->handler = $config['handler'];
        }
        
        // hanlder onworkerevent args
        if (is_array($config['dispatcher'])) {
            $config['dispatcher']['args'] = (array)$config['dispatcher']['args'];
            $this->dispatcher = array_merge($this->dispatcher, $config['dispatcher']);
            $this->dispatcher['callback'] = [
                $this->handler,
                'onWorkerDispatch'
            ];
        }
        
        // 附带选项
        if (is_array($config['options'])) {
            $this->options = array_merge($this->options, $config['options']);
        }
        
        // worker num
        if (isset($config['num']) && $config['num'] > 0) {
            $this->num = (int)$config['num'];
        }
        return true;
    }
    
    /**
     * 守护进程是否正常运行
     *
     * @return boolean|boolean|void
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