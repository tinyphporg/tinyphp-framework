<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name File.php
 * @author King
 * @version stable 2.0
 * @Date 2022年2月11日下午3:47:21
 * @Class List class
 * @Function List function_container
 * @History King 2022年2月11日下午3:47:21 2017年3月8日下午4:20:28 0 第一次建立该文件
 */
namespace Tiny\Log\Writer;

use Tiny\Log\LogException;

/**
 *
 * @package Tiny.Log.Writer
 *
 * @since 2013-12-10上午06:26:00
 * @final 2013-12-10上午06:26:00
 */
class File implements LogWriterInterface
{
    
    /**
     * 日志文件路径
     *
     * @var string
     */
    protected $path;
    
    /**
     * 构造函数
     *
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        $path = (string)$config['path'];
        if (!$path || !is_dir($path)) {
            throw new LogException(sprintf('Failed to instantiate FileLogwriter: the path %s is not a valid directory!', $path));
        }
        $this->path = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\Log\Writer\LogWriterInterface::write()
     */
    public function write($logId, $message, $priority)
    {
        $message = $logId . ' ' . $message;
        $logfile = $this->path . $logId . '.log';
        return file_put_contents($logfile, $message, FILE_APPEND | LOCK_EX);
    }
}

?>