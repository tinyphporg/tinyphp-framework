<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name Worker.php
 * @author King
 * @version Beta 1.0
 * @Date 2020年4月7日下午2:50:38
 * @Description
 * @Class List 1.
 * @Function List 1.
 * @History King 2020年4月7日下午2:50:38 第一次建立该文件
 *          King 2020年4月7日下午2:50:38 修改
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\Console\Worker;

/**
 * 执行指定请求次数的workers
 * options[tick = 0.1, runmax=1024]
 *
 * @package Tiny.Console.Worker
 * @since 2020年6月1日下午2:23:37
 * @final 2020年6月1日下午2:23:37
 */
class Worker extends Base
{
    
    /**
     * 最大执行次数
     *
     * @var int
     */
    protected $runmax = 1024;
    
    /**
     * 单次循环执行的停顿时间
     *
     * @var integer
     */
    protected $tick = 1000;
    
    /**
     * 构造函数
     *
     * @param array $options
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->formatWorkerOptions($this->options);
    }
    
    /**
     * worker运行
     *
     * {@inheritdoc}
     * @see \Tiny\Console\Worker\Base::run()
     */
    public function run()
    {
        if (!$this->handler) {
            return;
        }
        for ($i = $this->runmax; $i > 0; $i--) {
            if (!$this->daemonIsRunning()) {
                break;
            }
            $this->dispatch();
            usleep($this->tick * 1000);
        }
    }
    
    /**
     * 格式化worker的options
     *
     * @param array $options 配置数组
     */
    protected function formatWorkerOptions(array $options)
    {
        if (isset($options['runmax']) && intval($options['runmax']) > 0) {
            $this->runmax = (int)$options['runmax'];
        }
        if (isset($options['tick']) && floatval($options['tick']) > 0) {
            $this->tick = intval($options['tick'] * 1000);
        }
    }
}
?>