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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Console;

use Tiny\MVC\ConsoleApplication;
use Tiny\Console\Worker\IWorkerHandler;
use Tiny\Console\Worker\Base;

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
        'HOME_DIR' => FALSE,
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
    protected $_id = 'tinyphp-daemon';

    /**
     * 守护进程的配置选项
     *
     * @var array
     */
    protected $_options;

    /**
     * 调试模式 输出
     *
     * @var string
     */
    protected $_debug = FALSE;

    /**
     *
     * @var bool
     */
    protected $_isDaemon = TRUE;

    /**
     * 进程ID
     *
     * @var int
     * @access protected
     */
    protected $_pid = 0;

    /**
     * 进程权限
     *
     * @var integer
     */
    protected $_umask = 022;

    /**
     * 工作目录
     *
     * @var string
     */
    protected $_homedir = FALSE;

    /**
     * PID FILE路径
     *
     * @var string
     */
    protected $_pidFile;

    /**
     * worker日志ID
     *
     * @var string
     */
    protected $_logStatusId;

    /**
     * 错误日志ID
     *
     * @var string
     */
    protected $_logErrId;

    /**
     * 日志ID
     *
     * @var string
     */
    protected $_logId;

    /**
     * 日志输出handler
     *
     * @var IDaemonHandler
     */
    protected $_daemonHandler;

    /**
     * 动作名
     *
     * @var string
     */
    protected $_action;

    /**
     * 进程名
     *
     * @var string
     */
    protected $_processTitle;

    /**
     * master主进程管理的worker配置数组
     *
     * @var array
     * @access protected
     */
    protected $_workers = [];

    /**
     * 配置的最大worker数目
     *
     * @var integer
     */
    protected $_maxWorkers = 0;

    /**
     * 守护进程能支持的最大worker数目
     *
     * @var integer
     */
    protected $_maxDefaultWorkers = 0;

    /**
     * master进程保存的worker实例
     *
     * @var array
     */
    protected $_workerInstances = [];

    /**
     * fork后当前子进程运行的worker实例
     *
     * @var Base
     */
    protected $_currentWorkerInstance;

    /**
     * 构造化
     *
     * @param ConsoleApplication $app
     * @return void
     */
    public function __construct(string $id, array $options = [])
    {
        $this->_id = $id ?: self::DAEMON_OPTIONS['id'];
        $this->_checkEnv();
        $this->_initOptions($options);
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
        if (count($this->_workerInstances) + $workerNum > $this->_maxDefaultWorkers)
        {
            return;
        }
        $workerId = $worker->getId();
        if (key_exists($workerId, $this->_workers))
        {
            return;
        }

        $this->_workers[$workerId] = [
            'id' => $workerId,
            'instance' => $worker,
            'num' => $workerNum,
            'pids' => []
        ];
        $this->_maxWorkers += $workerNum;
    }

    /**
     * 根据配置数组添加workers 自动实例化
     *
     * @param array $workerArray
     */
    public function addWorkerByConfig(array $workers, IWorkerHandler $handler = NULL)
    {
        foreach ($workers as $worker)
        {
            $driverName = key_exists($worker['type'], self::WORKER_DRIVER_MAP) ? $worker['type'] : self::WORKER_DRIVER_DEFAULT;
            $className = self::WORKER_DRIVER_MAP[$driverName];
            $handler = ($worker['handler'] && $worker['handler'] instanceof IWorkerHandler) ? $worker['handler'] : $handler;
            if (!$handler)
            {
                $handler = NULL;
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
     * @param IDaemonHandler $handler
     */
    public function setDaemonHandler(IDaemonHandler $handler)
    {
        $this->_daemonHandler = $handler;
    }

    /**
     * 获取日志handler
     *
     * @return \Tiny\Console\IDaemonHandler
     */
    public function getDaemonHandler()
    {
        return $this->_daemonHandler;
    }

    /**
     * 发生信号事件
     *
     * @param int $signo
     */
    public function onsignal($signo)
    {
        switch ($signo)
        {
            case SIGINT:
                $this->_stop();
                break;
            case SIGTERM:
                $this->_stop(TRUE);
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
        switch ($this->_action)
        {
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
    protected function _checkEnv()
    {
        if (PHP_OS !== 'Linux')
        {
            throw new DaemonException('daemon模式 仅支持linux系统');
        }
        if (PHP_SAPI !== 'cli')
        {
            throw new DaemonException('daemon模式 仅支持在console模式下运行');
        }
        if (!version_compare(PHP_VERSION, '7.0.0', 'ge'))
        {
            throw new DaemonException('daemon模式 仅支持在PHP 7.0.0或以上版本运行');
        }
        if (strtolower(\php_uname('s')) === 'darwin')
        {
            throw new DaemonException('daemon模式 不支持在MAC OS下运行');
        }
    }

    /**
     * 停止守护进程并退出
     *
     * @return void
     */
    public function stop($isGraceful = TRUE)
    {
        if (!$this->_isRunning())
        {
            $errMsg = sprintf('pid file [%s] does not exist. Not running?\n', $this->_pidFile);
            return $this->_exit(1, $errMsg);
        }
        $pid = $this->_getPidFromPidFile();
        $sig = $isGraceful ? SIGTERM : SIGINT;
        posix_kill($pid, $sig);
        exit(0);
    }

    /**
     * 开始
     *
     * @return void
     */
    public function start()
    {
        if ($this->_isRunning())
        {
            echo sprintf("\npid file [%s] already exists, is it already running?\n", $this->_pidFile);
            exit(0);
        }

        // 进入后台守护模式
        $this->_daemonize();

        // 初始化所有worker
        $this->_initWorkers();

        // 开始守护进程
        while ($this->_isRunning())
        {
            $this->_keepWorkers();
            $this->_monitorWorkers();
        }
    }

    /**
     * 初始化守护进程参数
     *
     * @param array $options
     * @throws DaemonException
     */
    protected function _initOptions(array $options)
    {
        // piddir
        $piddir = $options['piddir'] ?: self::DEFAULT_OPTIONS['PID_DIR'];
        if (!is_dir($piddir) || !is_writable($piddir))
        {
            throw new DaemonException(sprintf('piddir:%s is not exists or is not writable!', $piddir));
        }

        $options['piddir'] = realpath($piddir) . DIRECTORY_SEPARATOR;
        $this->_pidFile = $options['piddir'] . $this->_id . '.pid';

        // 最大工作进程数
        $maxWorkers = (int)$options['maxworkers'];
        if ($maxWorkers <= 0)
        {
            $maxWorkers = self::DEFAULT_OPTIONS['MAX_WORKERS'];
        }
        $this->_maxDefaultWorkers = $maxWorkers;

        // homedir
        if (isset($options['homedir']) && is_dir($options['homedir']))
        {
            $this->_homedir = $options['homedir'];
        }

        // umask
        if (isset($options['umask']))
        {
            $this->_umask = $options['umask'];
        }

        // logID
        $this->_logErrId = $this->_id . '.err';
        $this->_logStatusId = $this->_id . '.status';
        $this->_logId = $this->_id;

        // 守护运行后执行的动作
        $action = (string)$options['action'];
        if (!in_array($action, self::DEFAULT_OPTIONS['ALLOW_ACTIONS']))
        {
            $action = self::DEFAULT_OPTIONS['ACTION'];
        }

        // debug输出
        $this->_debug = (bool)$options['debug'];

        // workers
        $this->_action = $action;
        $this->_options = $options;
    }

    /**
     * 初始化所有worker
     */
    protected function _initWorkers()
    {
        foreach ($this->_workers as $worker)
        {
            $worker['instance']->setDaemonOptions($this->_pid, $this->_pidFile);
            $worker['instance']->init();
        }
    }

    /**
     * 保持workers
     *
     * @return void
     */
    protected function _keepWorkers()
    {
        if (count($this->_workerInstances) >= $this->_maxWorkers)
        {
            return;
        }
        $worker = $this->_getFreeWorkerDetail();
        if (!$worker)
        {
            return;
        }
        $pid = pcntl_fork();
        if ($pid == -1)
        {
            return $this->_exit(1, 'worker create faild!');
        }
        if ($pid)
        {
            // master
            return $this->_addWorkerToMaster($worker['id'], $pid);
        }

        // worker
        $this->_dispathByWorker($worker);
    }

    /**
     * 获取appName
     *
     * @return mixed
     */
    protected function _getFreeWorkerDetail()
    {
        foreach ($this->_workers as $worker)
        {
            if (count($worker['pids']) < $worker['num'])
            {
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
    protected function _addWorkerToMaster($id, $pid)
    {
        $this->_workers[$id]['pids'][$pid] = $pid;
        $this->_workerInstances[$pid] = $this->_workers[$id]['instance'];
        $this->_status(sprintf('Worker id[%s] process created successfully, PID %s', $id, $pid));
    }

    /**
     * 根据PID删除主进程管理的worker信息
     *
     * @param int $pid
     */
    protected function _deleteExitedWorkerByPid($pid)
    {
        $id = $this->_workerInstances[$pid]->getId();
        unset($this->_workerInstances[$pid]);
        unset($this->_workers[$id]['pids'][$pid]);
        $this->_status(sprintf('Worker id[%s] process deleted successfully, PID %s', $id, $pid));
    }

    /**
     * 检测运行的worker信息 有子进程退出则执行清理和补充动作
     *
     * @return void
     */
    protected function _monitorWorkers()
    {
        if (count($this->_workerInstances) < $this->_maxWorkers)
        {
            return;
        }
        $status = 0;
        $pid = pcntl_wait($status, WUNTRACED);
        if ($pid == -1)
        {
            $this->_delPidFile();
            $this->_exit(1, 'pcntl_wait status error');
        }
        $this->_deleteExitedWorkerByPid($pid);
    }

    /**
     * 初始化主进程
     *
     * @throws DaemonException
     */
    protected function _daemonize()
    {
        $pid = pcntl_fork();
        if ($pid > 0)
        {
            exit(0);
        }
        if (-1 === $pid)
        {
            return $this->_exit(1, 'daemonize failed!');
        }

        posix_setsid();

        $pid = pcntl_fork();
        if ($pid > 0)
        {
            exit(0);
        }
        if (-1 === $pid)
        {
            return $this->_exit(1, 'daemonize failed!');
        }

        // 写入PID文件
        $this->_pid = posix_getpid();
        if (!file_put_contents($this->_pidFile, $this->_pid, LOCK_EX))
        {
            return $this->_exit(1, 'daemonize failed, pid file %s write faild');
        }

        // 进程重命名
        $this->_processTitle = sprintf('%s %s -d --daemon-id=%s ', $_SERVER['_'], realpath($_SERVER['PHP_SELF']), $this->_id);

        // 变更工作目录
        if ($this->_homedir && is_dir($this->_homedir))
        {
            chdir($this->_homedir);
        }

        // 进程权限
        if ($this->_umask)
        {
            umask($this->_umask);
        }

        $this->_initSignal();

        // 重命名主进程
        $this->_setProcessTitle($this->_processTitle . ' process master');
        $this->_status(sprintf('Master process %s is inited, PID %d ', $this->_policy['id'], $this->_pid));
    }

    /**
     * 设置进程名称
     *
     * @param string $title
     * @return boolean
     */
    protected function _setProcessTitle($title)
    {
        return cli_set_process_title($title);
    }

    /**
     * 主进程初始化信号处理
     *
     * @return void
     */
    protected function _initSignal()
    {
        // 守护进程异步处理
        pcntl_async_signals(TRUE);

        // stop
        pcntl_signal(SIGINT, [
            $this,
            "onsignal"
        ], FALSE);

        // stop
        pcntl_signal(SIGTERM, [
            $this,
            "onsignal"
        ], FALSE);

        // stop
        pcntl_signal(SIGTSTP, [
            $this,
            "onsignal"
        ], FALSE);

        // 关闭管道事件
        pcntl_signal(SIGPIPE, SIG_IGN, FALSE);
    }

    /**
     * 执行子进程的APP实例
     *
     * @access protected
     * @param string $appName
     *            app名称
     * @param string $profile
     *            配置文件路径
     * @return void
     */
    protected function _dispathByWorker($worker)
    {
        // worker
        $this->_isDaemon = FALSE;

        // rename process name for ps -ef
        $this->_setProcessTitle($this->_processTitle . ' process worker ' . $worker['id']);

        // 统一收集worker运行过程中的log
        ob_start([
            $this,
            'onWorkerOutput'
        ], 1);

        // worker
        $this->_currentWorkerInstance = $worker['instance'];

        // exit事件
        register_shutdown_function([
            $this,
            'onWorkerExit'
        ]);

        try
        {
            // onstart事件
            $startResult = $this->_currentWorkerInstance->start();

            // start事件失败即终止运行
            if (FALSE === $startResult)
            {
                $this->_log(1, sprintf('Worker PID %d onstart faild', $this->_pid));
                exit(1);
            }
            // r运行事件
            $this->_currentWorkerInstance->run();
        }
        catch (DaemonException $e)
        {
            $this->_log(sprintf("Worker Exception:  %s Line:%d File:%s", $e->getMessage(), $e->getLine(), $e->getFile()));
            exit(1);
        }
        exit(0);
    }

    /**
     *
     * @param string $output
     */
    public function onWorkerOutput($output)
    {
        if (!$output)
        {
            return FALSE;
        }
        $this->_log($output);
        return NULL;
    }

    /**
     */
    public function onWorkerExit()
    {
        if ($this->_isRunning())
        {
            return;
        }
        // worker stop事件
        $this->_onWorkerStop();
    }

    /**
     * worker进程停止工作
     */
    protected function _onWorkerStop($isGraceful = TRUE)
    {
        // 优雅关闭 则执行stop事件
        if ($isGraceful && $this->_currentWorkerInstance)
        {
            $this->_currentWorkerInstance->stop();
        }
    }

    /**
     * 通过输入参数初始化守护进程
     *
     * @return void
     */
    protected function _getPidFromPidFile()
    {
        if (!file_exists($this->_pidFile))
        {
            return FALSE;
        }
        $pid = (int)file_get_contents($this->_pidFile);
        return $pid;
    }

    /**
     * 检测主进程ID是否正在运行中
     */
    protected function _isRunning()
    {
        $pid = $this->_getPidFromPidFile();
        if (!$pid)
        {
            return FALSE;
        }
        if ($this->_pid > 0 && $pid != $this->_pid)
        {
            return FALSE;
        }
        $pidIsExists = file_exists('/proc/' . $pid);
        return $pidIsExists ? $pid : FALSE;
    }

    /**
     * 停止
     *
     * @param bool $isGraceful
     */
    protected function _stop(bool $isGraceful = TRUE)
    {
        // worker进程接受
        if (!$this->_isDaemon)
        {
            $this->_onWorkerStop($isGraceful);
            exit(0);
        }

        // daemon接受
        $sig = $isGraceful ? SIGTERM : SIGINT;
        foreach ($this->_workerInstances as $pid => $worker)
        {
            posix_kill($pid, $sig);
        }
        if ($isGraceful)
        {
            sleep(2);
            foreach ($this->_workerInstances as $pid => $worker)
            {
                posix_kill($pid, SIGINT);
            }
        }
        $this->_delPidFile();
        exit(0);
    }

    /**
     * 删除PID文件 如果有
     *
     * @return void
     */
    protected function _delPidFile()
    {
        if (file_exists($this->_pidFile))
        {
            unlink($this->_pidFile);
        }
    }

    /**
     * 结束主进程
     *
     * @param int $status
     *            状态码
     * @param string $log
     *            退出时的日志
     * @return void
     */
    protected function _exit($status = 0, $msg = NULL, $priority = 3)
    {
        if ($msg && $status == 0)
        {
            $this->_status($msg, $priority);
        }
        if ($msg && $status != 0)
        {
            $this->_err($msg, $priority);
        }
        $this->_stop();
        exit($status);
    }

    /**
     * 记录错误
     *
     * @param string $msg
     * @param
     */
    protected function _err($msg, $priority = 3)
    {
        return $this->_outlog($this->_logErrId, $msg, $priority);
    }

    /**
     * 状态日志
     *
     * @param string $msg
     * @param int $priority
     *            日志优先级
     */
    protected function _status($msg, $priority = 6)
    {
        return $this->_outlog($this->_logStatusId, $msg, $priority);
    }

    /**
     * 进程日志
     *
     * @param string $msg
     * @param int $priority
     *            优先级
     */
    protected function _log($msg, $priority = 6)
    {
        return $this->_outlog($this->_logId, $msg, $priority);
    }

    /**
     * 写入日志文件
     *
     * @param string $id
     *            日志ID
     * @param string $msg
     *            日志内容
     * @param int $priority
     *            日志优先级
     *            参数数组
     * @return void
     */
    protected function _outlog($id, $msg, $priority = 6)
    {
        $msg .= "\n";
        if (true || $this->_debug)
        {
            echo $msg;
        }
        if ($this->_daemonHandler)
        {
            $this->_daemonHandler->onOutLog($id, $msg, $priority);
        }
    }
}
?>