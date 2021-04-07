<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name File.php
 * @author King
 * @version 1.0
 * @Date: 2013-12-10上午06:17:00
 * @Description
 * @Class List
 * @Function
 * @History <author> <time> <version > <desc>
 *          king 2013-12-10上午06:17:00 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0.01 审定
 */
namespace Tiny\Log\Writer;

/**
 * 本地文件日志写入器
 *
 * @package Tiny.Log.Writer
 * @since 2013-12-10上午06:17:26
 * @final 2013-12-10上午06:17:26
 */
use Tiny\Log\LogException;

/**
 *
 * @package Tiny.Log.Writer
 *
 * @since 2013-12-10上午06:26:00
 * @final 2013-12-10上午06:26:00
 */
class File implements IWriter
{

    /**
     * 默认的策略数组
     *
     * @var array
     */
    protected $_policy = [
        'path' => NULL
    ];

    /**
     * 构造函数
     *
     * @param array $policy
     *        策略配置
     * @return void
     */
    public function __construct(array $policy = [])
    {
        $this->_policy = array_merge($this->_policy, $policy);
        if (!is_dir($this->_policy['path']))
        {
            throw new LogException(sprintf('实例化Tiny\Log\Writer\File失败，路径%s没有设置为有效目录', $this->_policy['path']));
        }
        $this->_policy['path'] = rtrim($this->_policy['path'], '\\/') . DIRECTORY_SEPARATOR;
    }

    /**
     * 执行日志写入
     *
     * @param string $id
     *        日志ID
     * @param string $message
     *        日志内容
     * @return void
     */
    public function doWrite($id, $message, $priority)
    {
        $message = $id . ' ' . $message;
        $path = $this->_policy['path'] . $id . '.log';
        return file_put_contents($path, $message, FILE_APPEND|LOCK_EX);
    }
}
?>