<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Daemon.php
 * @author King
 * @version Beta 1.0
 * @Date 2017年4月7日下午8:41:15
 * @Class List
 * @Function List
 * @History King 2017年4月7日下午8:41:15 0 第一次建立该文件
 *          King 2017年4月7日下午8:41:15 1 上午修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 */
namespace Tiny\Console;

use Tiny\Console\Worker\Base;
use Tiny\Console\Worker\WorkerHandlerInterface;

/**
 * 命令行守护进程类
 *
 * @package
 * @since 2017年4月7日下午8:41:41
 * @final 2017年4月7日下午8:41:41
 */
class Daemon
{
    
    /**
     * 默认配置参数
     *
     * @var array
     */
    const DEFAULT_OPTIONS = [
        'ID' => 'tinyd',
        'UMASK' => 022,
        'HOME_DIR' => false,
        'MAX_WORKERS' => 1024,
        'PID_DIR' => '/var/run',
        'LOG_DIR' => '/var/log',
        'ACTION' => 'start',
        'ALLOW_ACTIONS' => [
            'start',
            'stop',
            'reload'
        ]
    ];
    
    /**
     * 默认的worker驱动类型
     *
     * @var string
     */
    const WORKER_DRIVER_DEFAULT = 'worker';
    
    /**
     * worker驱动map
     *
     * @var array
     */
    const WORKER_DRIVER_MAP = [
        'worker' => '\Tiny\Console\Worker\Worker'
    ];
    
    /**
     * 默认的daemonID
     *
     * @var string
     */
    protected $id = 'tinyphp-daemon';
    
    /**
     * 守护进程的配置选项
     *
     * @var array
     */
    protected $options;
    
    /**
     * 调试模式 输出
     *
     * @var string
     */
    protected $debug = false;
    
    /**
     *
     * @var bool
     */
    protected $isDaemon = true;
    
    /**
     * 进程ID
     *
     * @var int
     * @access protected
     */
    protected $pid = 0;
    
    /**
     * 进程权限
     *
     * @var integer
     */
    protected $umask = 022;
    
    /**
     * 工作目录
     *
     * @var string
     */
    protected $homedir = false;
    
    /**
     * PID FILE路径
     *
     * @var string
     */
    protected $pidFile;
    
    /**
     * worker日志ID
     *
     * @var string
     */
    protected $logStatusId;
    
    /**
     * 错误日志ID
     *
     * @var string
     */
    protected $logErrId;
    
    /**
     * 日志ID
     *
     * @var string
     */
    protected $logId;
    
    /**
     * 日志输出handler
     *
     * @var DaemonHandlerInterface
     */
    protected $daemonHandler;
    
    /**
     * 动作名
     *
     * @var string
     */
    protected $action;
    
    /**
     * 进程名
     *
     * @var string
     */
    protected $processTitle;
    
    /**
     * master主进程管理的worker配置数组
     *
     * @var array
     * @access protected
     */
    protected $workers = [];
    
    /**
     * 配置的最大worker数目
     *
     * @var integer
     */
    protected $maxWorkers = 0;
    
    /**
     * 守护进程能支持的最大worker数目
     *
     * @var integer
     */
    protected $maxDefaultWorkers = 0;
    
    /**
     * master进程保存的worker实例
     *
     * @var array
     */
    protected $workerInstances = [];
    
    /**
     * fork后当前子进程运行的worker实例
     *
     * @var Base
     */
    protected $currentWorkerInstance;
    
    /**
     * 构造化
     *
     * @return void
     */
    public function __construct(string $id, array $options = [])
    {
        $this->id = $id ?: self::DAEMON_OPTIONS['id'];
        $this->checkEnv();
        $this->initOptions($options);
    }
    
    /**
     * 添加worker实例和对应的进程数目
     *
     * @param \Tiny\Console\Worker\Base $worker
     * @param int $num
     */
    public function addWorker(\Tiny\Console\Worker\Base $worker)
    {
        $workerNum = $worker->getNum();
        if (count($this->workerInstances) + $workerNum > $this->maxDefaultWorkers) {
            return;
        }
        $workerId = $worker->getId();
        if (key_exists($workerId, $this->workers)) {
            return;
        }
        
        $this->workers[$workerId] = [
            'id' => $workerId,
            'instance' => $worker,
            'num' => $workerNum,
            'pids' => []
        ];
        $this->maxWorkers += $workerNum;
    }
    
    /**
     * 根据配置数组添加workers 自动实例化
     *
     * @param array $workerArray
     */
    public function addWorkerByConfig(array $workers, WorkerHandlerInterface $handler = null)
    {
        foreach ($workers as $worker) {
            $driverName = key_exists($worker['type'], self::WORKER_DRIVER_MAP) ? $worker['type'] : self::WORKER_DRIVER_DEFAULT;
            $className = self::WORKER_DRIVER_MAP[$driverName];
            $handler = ($worker['handler'] && $worker['handler'] instanceof WorkerHandlerInterface) ? $worker['handler'] : $handler;
            if (!$handler) {
                $handler = null;
            }
            
            $args = isset($worker['args']) && is_array($worker['args']) ? $worker['args'] : [];
            $worker['args'] = $args;
            $worker['num'] = (int)$worker['num'] ?: 0;
            $worker['handler'] = $handler;
            $workerInstance = new $className($worker);
            $this->addWorker($workerInstance);
        }
    }
    
    /**
     * 设置日志handler
     *
     * @param DaemonHandlerInterface $handler
     */
    public function setDaemonHandler(DaemonHandlerInterface $handler)
    {
        $this->daemonHandler = $handler;
    }
    
    /**
     * 获取日志handler
     *
     * @return DaemonHandlerInterface
     */
    public function getDaemonHandler(): DaemonHandlerInterface
    {
        return $this->daemonHandler;
    }
    
    /**
     * 发生信号事件
     *
     * @param int $signo
     */
    public function onsignal($signo)
    {
        switch ($signo) {
            case SIGINT:
                $this->end();
                break;
            case SIGTERM:
                $this->end(true);
                break;
        }
    }
    
    /**
     * 守护运行应用程序实例
     *
     * @return bool
     */
    public function run()
    {
        switch ($this->action) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'reload':
                $this->restart();
                break;
            default:
                $this->start();
                break;
        }
    }
    
    /**
     * 检测运行环境
     *
     * @throws DaemonException
     * @return void
     */
    protected function checkEnv()
    {
        if (PHP_OS !== 'Linux') {
            throw new DaemonException(sprintf('%s is only allowed to run on Linux systems', self::class));
        }
        if (PHP_SAPI !== 'cli') {
            throw new DaemonException(sprintf('%s is only allowed to run on the command line', self::class));
        }
        if (!version_compare(PHP_VERSION, '7.0.0', 'ge')) {
            throw new DaemonException(sprintf('%s is only allowed to run in PHP 7.0 or later', self::class));
        }
        if (strtolower(\php_uname('s')) === 'darwin') {
            throw new DaemonException('%s does not support running on MacOS', self::class);
        }
    }
    
    /**
     * 停止守护进程并退出
     *
     * @return void
     */
    public function stop($isGraceful = true)
    {
        if (!$this->isRunning()) {
            $errMsg = sprintf('pid file [%s] does not exist. Not running?\n', $this->pidFile);
            return $this->exit(1, $errMsg);
        }
        $pid = $this->getPidFromPidFile();
        $sig = $isGraceful ? SIGTERM : SIGINT;
        posix_kill($pid, $sig);
    }
    
    /**
     * 开始
     *
     * @return void
     */
    public function start()
    {
        if ($this->isRunning()) {
            echo sprintf("\npid file [%s] already exists, is it already running?\n", $this->pidFile);
            exit(0);
        }
        
        // 进入后台守护模式
        $this->daemonize();
        
        // 初始化所有worker
        $this->initWorkers();
        
        // 开始守护进程
        while ($this->isRunning()) {
            $this->keepWorkers();
            $this->monitorWorkers();
        }
    }
    
    /**
     * 初始化守护进程参数
     *
     * @param array $options
     * @throws DaemonException
     */
    protected function initOptions(array $options)
    {
        // piddir
        $piddir = $options['piddir'] ?: self::DEFAULT_OPTIONS['PID_DIR'];
        if (!is_dir($piddir) || !is_writable($piddir)) {
            throw new DaemonException(sprintf('piddir:%s is not exists or is not writable!', $piddir));
        }
        
        $options['piddir'] = realpath($piddir) . DIRECTORY_SEPARATOR;
        $this->pidFile = $options['piddir'] . $this->id . '.pid';
        
        // 最大工作进程数
        $maxWorkers = (int)$options['maxworkers'];
        if ($maxWorkers <= 0) {
            $maxWorkers = self::DEFAULT_OPTIONS['MAX_WORKERS'];
        }
        $this->maxDefaultWorkers = $maxWorkers;
        
        // homedir
        if (isset($options['homedir']) && is_dir($options['homedir'])) {
            $this->homedir = $options['homedir'];
        }
        
        // umask
        if (isset($options['umask'])) {
            $this->umask = $options['umask'];
        }
        
        // logID
        $this->logErrId = $this->id . '.err';
        $this->logStatusId = $this->id . '.status';
        $this->logId = $this->id;
        
        // 守护运行后执行的动作
        $action = (string)$options['action'];
        if (!in_array($action, self::DEFAULT_OPTIONS['ALLOW_ACTIONS'])) {
            $action = self::DEFAULT_OPTIONS['ACTION'];
        }
        
        // debug输出
        $this->debug = (bool)$options['debug'];
        
        // workers
        $this->action = $action;
        $this->options = $options;
    }
    
    /**
     * 初始化所有worker
     */
    protected function initWorkers()
    {
        foreach ($this->workers as $worker) {
            $worker['instance']->setDaemonOptions($this->pid, $this->pidFile);
            $worker['instance']->init();
        }
    }
    
    /**
     * 保持workers
     *
     * @return void
     */
    protected function keepWorkers()
    {
        if (count($this->workerInstances) >= $this->maxWorkers) {
            return;
        }
        $worker = $this->getFreeWorkerDetail();
        if (!$worker) {
            return;
        }
        $pid = pcntl_fork();
        if ($pid == -1) {
            return $this->exit(1, 'worker create faild!');
        }
        if ($pid) {
            // master
            return $this->addWorkerToMaster($worker['id'], $pid);
        }
        
        // worker
        $this->dispathByWorker($worker);
    }
    
    /**
     * 获取appName
     *
     * @return mixed
     */
    protected function getFreeWorkerDetail()
    {
        foreach ($this->workers as $worker) {
            if (count($worker['pids']) < $worker['num']) {
                return $worker;
            }
        }
    }
    
    /**
     * 添加创建的worker进程到master主进程管理
     *
     * @param string $id
     * @param string $pid
     */
    protected function addWorkerToMaster($id, $pid)
    {
        $this->workers[$id]['pids'][$pid] = $pid;
        $this->workerInstances[$pid] = $this->workers[$id]['instance'];
        $this->status(sprintf('Worker id[%s] process created successfully, PID %s', $id, $pid));
    }
    
    /**
     * 根据PID删除主进程管理的worker信息
     *
     * @param int $pid
     */
    protected function deleteExitedWorkerByPid($pid)
    {
        $id = $this->workerInstances[$pid]->getId();
        unset($this->workerInstances[$pid]);
        unset($this->workers[$id]['pids'][$pid]);
        $this->status(sprintf('Worker id[%s] process deleted successfully, PID %s', $id, $pid));
    }
    
    /**
     * 检测运行的worker信息 有子进程退出则执行清理和补充动作
     *
     * @return void
     */
    protected function monitorWorkers()
    {
        if (count($this->workerInstances) < $this->maxWorkers) {
            return;
        }
        $status = 0;
        $pid = pcntl_wait($status, WUNTRACED);
        if ($pid == -1) {
            $this->delPidFile();
            $this->exit(1, 'pcntl_wait status error');
        }
        $this->deleteExitedWorkerByPid($pid);
    }
    
    /**
     * 初始化主进程
     *
     * @throws DaemonException
     */
    protected function daemonize()
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            exit(0);
        }
        if (-1 === $pid) {
            return $this->exit(1, 'daemonize failed!');
        }
        
        posix_setsid();
        
        $pid = pcntl_fork();
        if ($pid > 0) {
            exit(0);
        }
        if (-1 === $pid) {
            return $this->exit(1, 'daemonize failed!');
        }
        
        // 写入PID文件
        $this->pid = posix_getpid();
        if (!file_put_contents($this->pidFile, $this->pid, LOCK_EX)) {
            return $this->exit(1, sprintf('daemonize failed, pid file %s write faild', $this->pidFile));
        }
        
        // 进程重命名
        $this->processTitle = sprintf('%s %s -d --daemon-id=%s ', $_SERVER['_'], realpath($_SERVER['PHP_SELF']),
            $this->id);
        
        // 变更工作目录
        if ($this->homedir && is_dir($this->homedir)) {
            chdir($this->homedir);
        }
        
        // 进程权限
        if ($this->umask) {
            umask($this->umask);
        }
        
        $this->initSignal();
        
        // 重命名主进程
        $this->setProcessTitle($this->processTitle . ' process master');
        $this->status(sprintf('Master process %s is inited, PID %d ', $this->policy['id'], $this->pid));
    }
    
    /**
     * 设置进程名称
     *
     * @param string $title
     * @return boolean
     */
    protected function setProcessTitle($title)
    {
        return cli_set_process_title($title);
    }
    
    /**
     * 主进程初始化信号处理
     *
     * @return void
     */
    protected function initSignal()
    {
        // 守护进程异步处理
        pcntl_async_signals(true);
        
        // stop
        pcntl_signal(SIGINT, [
            $this,
            "onsignal"
        ], false);
        
        // stop
        pcntl_signal(SIGTERM, [
            $this,
            "onsignal"
        ], false);
        
        // stop
        pcntl_signal(SIGTSTP, [
            $this,
            "onsignal"
        ], false);
        
        // 关闭管道事件
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }
    
    /**
     * 执行子进程的APP实例
     *
     * @access protected
     * @param string $appName app名称
     * @param string $profile 配置文件路径
     * @return void
     */
    protected function dispathByWorker($worker)
    {
        // worker
        $this->isDaemon = false;
        
        // rename process name for ps -ef
        $this->setProcessTitle($this->processTitle . ' process worker ' . $worker['id']);
        
        // 统一收集worker运行过程中的log
        ob_start(function($output){
            if ($output){
                $this->log($output);
            }
        }, 1);
        
        // worker
        $this->currentWorkerInstance = $worker['instance'];
        
        // exit事件
        register_shutdown_function(function(){
            if ($this->isRunning()) {
                return;
            }
            // worker stop事件
            $this->onWorkerStop();
        });
        
        try {
            // start事件失败即终止运行
            if (false === $this->currentWorkerInstance->start()) {
                $this->log(sprintf('Worker PID %d onstart faild', $this->pid), 1);
                return $this->stop(true);
            }
            
            // 运行事件
            $this->currentWorkerInstance->run();
        } catch (DaemonException $e) {
            $this->log(sprintf("Worker Exception:  %s Line:%d File:%s", $e->getMessage(), $e->getLine(), $e->getFile()));
            exit(1);
        }
        exit(0);
    }
    
    /**
     * worker进程停止工作
     */
    protected function onWorkerStop($isGraceful = true)
    {
        // 优雅关闭 则执行stop事件
        if ($isGraceful && $this->currentWorkerInstance) {
            $this->currentWorkerInstance->stop();
        }
    }
    
    /**
     * 通过输入参数初始化守护进程
     */
    protected function getPidFromPidFile()
    {
        if (!file_exists($this->pidFile)) {
            return false;
        }
        $pid = (int)file_get_contents($this->pidFile);
        return $pid;
    }
    
    /**
     * 检测主进程ID是否正在运行中
     */
    protected function isRunning()
    {
        $pid = $this->getPidFromPidFile();
        if (!$pid) {
            return false;
        }
        if ($this->pid > 0 && $pid != $this->pid) {
            return false;
        }
        $pidIsExists = file_exists('/proc/' . $pid);
        return $pidIsExists ? $pid : false;
    }
    
    /**
     * 停止
     *
     * @param bool $isGraceful
     */
    protected function end(bool $isGraceful = true)
    {
        // worker进程
        if (!$this->isDaemon) {
            $this->onWorkerStop($isGraceful);
            exit(0);
        }
        
        // daemon进程
        $sig = $isGraceful ? SIGTERM : SIGINT;
        foreach ($this->workerInstances as $pid => $worker) {
            posix_kill($pid, $sig);
        }
        if ($isGraceful) {
            sleep(2);
            foreach ($this->workerInstances as $pid => $worker) {
                posix_kill($pid, SIGINT);
            }
        }
        $this->delPidFile();
        exit(0);
    }
    
    /**
     * 删除PID文件 如果有
     */
    protected function delPidFile()
    {
        if (file_exists($this->pidFile)) {
            unlink($this->pidFile);
        }
    }
    
    /**
     * 结束主进程
     *
     * @param int $status 状态码
     * @param string $log 退出时的日志
     * @return void
     */
    protected function exit($status = 0, $msg = null, $priority = 3)
    {
        if ($msg && $status == 0) {
            $this->status($msg, $priority);
        }
        if ($msg && $status != 0) {
            $this->err($msg, $priority);
        }
        $this->end();
        exit($status);
    }
    
    /**
     * 记录错误
     *
     * @param string $msg
     * @param
     */
    protected function err($msg, $priority = 3)
    {
        return $this->outlog($this->logErrId, $msg, $priority);
    }
    
    /**
     * 状态日志
     *
     * @param string $msg
     * @param int $priority 日志优先级
     */
    protected function status($msg, $priority = 6)
    {
        return $this->outlog($this->logStatusId, $msg, $priority);
    }
    
    /**
     * 进程日志
     *
     * @param string $msg
     * @param int $priority 优先级
     */
    protected function log($msg, $priority = 6)
    {
        return $this->outlog($this->logId, $msg, $priority);
    }
    
    /**
     * 写入日志文件
     *
     * @param string $id 日志ID
     * @param string $msg 日志内容
     * @param int $priority 日志优先级
     */
    protected function outlog($id, $msg, $priority = 6)
    {
        $msg .= "\n";
        if (true || $this->debug) {
            echo $msg;
        }
        if ($this->daemonHandler) {
            @$this->daemonHandler->onOutLog($id, $msg, $priority);
        }
    }
}
?>