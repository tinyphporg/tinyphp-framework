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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
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
    protected $_pid = 0;

    /**
     * 系统用户ID
     *
     * @var integer
     */
    protected $_uid = FALSE;

    /**
     * 系统用户组ID
     *
     * @var integer
     */
    protected $_gid = FALSE;

    /**
     * workerID
     *
     * @var integer
     */
    protected $_id;

    /**
     * 默认开启的工作进程
     *
     * @var integer
     */
    protected $_num = 1;

    /**
     * 执行worker委托的代理实例
     *
     * @var IWorkerHandler
     */
    protected $_handler;

    /**
     * 策略数组
     *
     * @var array
     */
    protected $_options = [];

    /**
     * 参数
     * @var array
     */
    protected $_args = [];

    /**
     * 构造函数
     */
    public function __construct(array $options = [])
    {
        $ret = $this->_formatOptions($options);
        if(!$ret)
        {
            throw new WorkerException('Worker Excetion: options is format faild!');
        }
    }

    /**
     * 获取worker的ID 进程间通讯
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * 获取worker的进程数
     *
     * @return int
     */
    public function getNum()
    {
        return $this->_num;
    }

    /**
     * 设置worker回调的handler实例
     *
     * @param IWorkerHandler $whandler
     */
    public function setWorkerHandler(IWorkerHandler $whandler)
    {
        $this->_handler = $whandler;
    }

    /**
     * 获取设置的worker回调的handler实例
     *
     * @return mixed
     */
    public function getWorkerHandler()
    {
        return $this->_handler;
    }

    /**
     * 初始化 在成为子进程之前
     *
     * @return boolean
     */
    public function init()
    {
        return TRUE;
    }

    /**
     * 成为子进程后 开始
     *
     * @return boolean
     */
    public function start()
    {
        // init pid
        $this->_pid = posix_getpid();
        return $this->onstart();
    }

    /**
     * exit 退出后
     *
     * @return boolean
     */
    public function stop()
    {
        return $this->onstop();
    }

    /**
     * 调用handler的函数
     *
     * @param string $method
     * @param array $args
     * @return boolean|mixed
     */
    public function __call($method, $args)
    {
        if (!$this->_handler)
        {
            return NULL;
        }

        $isEvent = FALSE;
        if (substr($method, 0, 2) == 'on')
        {
            $isEvent = TRUE;
        }
        // 参数
        $args[] = $this->_args;

        // callback
        $callback = [
            $this->_handler,
            'onWorkerDispatch'
        ];

        // param array
        $params = [
            $method,
            $args,
            $isEvent
        ];

        // call callback
        $ret = call_user_func_array($callback, $params);
        return $ret;
    }

    /**
     * worker正式运行
     */
    abstract public function run();

    /**
     * 格式化选项数组
     *
     * @param array $options
     * @throws WorkerException
     */
    protected function _formatOptions(array $options)
    {
        $options = array_merge($this->_options, $options);
        print_r($options['id']);
        if (!$options['id'])
        {
            return FALSE;
        }
        $this->_id = $options['id'];

        // hanlder onworkerevent args
        if (is_array($options['args']))
        {
            $this->_args = array_merge($this->_args, $options['args']);
        }

        // handler
        if ($options['handler'] && $options['handler'] instanceof IWorkerHandler)
        {
            $this->_handler = $options['handler'];
        }

        // worker num
        if (isset($options['num']) && $options['num'] > 0)
        {
            $this->_num = (int)$options['num'];
        }

        // process uid
        if (isset($this->_options['uid']))
        {
            $this->_uid = (int)$this->_options['uid'];
        }

        // process gid
        if (isset($this->_options['gid']))
        {
            $this->_gid = (int)$this->_options['gid'];
        }

        return TRUE;
    }
}
?>