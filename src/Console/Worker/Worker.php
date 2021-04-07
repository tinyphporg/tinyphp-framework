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
 *          King 2020年6月1日14:21 stable 1.0.01 审定
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
     * 执行worker委托的回调函数
     *
     * @var callable
     */
    protected $_action;

    /**
     * 最大执行次数
     *
     * @var int
     */
    protected $_runmax = 1024;

    /**
     * 单次循环执行的停顿时间
     *
     * @var integer
     */
    protected $_tick = 1000;

    /**
     * 构造函数
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        if (isset($options['runmax']) && intval($options['runmax']) > 0)
        {
            $this->_runmax = (int)$options['runmax'];
        }
        if (isset($options['tick']) && floatval($options['tick']) > 0)
        {
            $this->_tick = intval($options['tick'] * 1000);
        }
        $this->_action = $this->_args['action'] ?: 'index';
    }

    /**
     * worker运行
     *
     * {@inheritdoc}
     * @see \Tiny\Console\Worker\Base::run()
     */
    public function run()
    {
        if (!$this->_handler)
        {
            return;
        }
        for ($i = $this->_runmax; $i > 0; $i--)
        {
            $this->__call($this->_action, $this->_args);
            usleep($this->_tick * 1000);
        }
    }
}
?>