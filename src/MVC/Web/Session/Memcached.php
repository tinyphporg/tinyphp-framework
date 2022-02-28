<?php
/**
 *
 * @copyright (C), 2011-, King.
 * @name SessionMemcache.php
 * @author King
 * @version Beta 1.0
 * @Date: Sat Nov 12 23:16 52 CST 2011
 * @Description
 * @Class List
 *        1.
 * @History <author> <time> <version > <desc>
 *          King 2012-5-14上午08:22:34 Beta 1.0 第一次建立该文件
 *          King 2020年6月1日14:21 stable 1.0 审定
 *
 */
namespace Tiny\MVC\Web\Session;

use Tiny\Data\Memcached\Memcached as MemcachedHandler;
use Tiny\Tiny;

/**
 * Session后端Redis适配器
 *
 * @package Tiny.MVC.Http.Session
 * @since : 2013-4-13上午02:27:53
 * @final : 2013-4-13上午02:27:53
 */
class Memcached implements SessionAdapterInterface
{
    
    /**
     * Redis的data操作实例
     *
     * @var MemcachedHandler
     */
    protected $memcached;
    
    /**
     * 过期时间
     *
     * @var integer
     */
    protected $expires = 3600;
    
    /**
     * data 资源池ID
     *
     * @var string
     */
    protected $dataId;
    
    /**
     * 初始化构造函数
     *
     * @param array $policy 配置
     */
    function __construct(array $config = [])
    {
        $expires = (int)$config['expires'];
        if ($expires > 0) {
            $this->expires = $expires;
        }
        $dataId = (string)$config['dataid'];
        if (!$dataId) {
            throw new SessionException(sprintf("Initialization %s failed, profile.session.dataid is required!", __CLASS__));
        }
        $this->dataId = $dataId;
    }
    
    /**
     * 打开Session
     *
     * @return true
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }
    
    /**
     * 关闭Session
     */
    public function close()
    {
        return true;
    }
    
    /**
     * 读Session
     *
     * @param string $sessionId Session身份标示
     * @return string
     */
    public function read($sessionId)
    {
        return $this->getMemcached()->get($sessionId);
    }
    
    /**
     * 写Session
     *
     * @param string $sessionId SessionID标示
     * @param string $sessionValue Session值
     * @return bool
     */
    public function write($sessionId, $sessionValue)
    {
        return $this->getMemcached()->set($sessionId, $sessionValue, $this->expires);
    }
    
    /**
     * 注销某个变量
     *
     * @param string $sessionId Session身份标示
     * @return bool
     */
    public function destroy($sessionId)
    {
        return $this->getMemcached()->delete($sessionId);
    }
    
    /**
     * 自动回收过期变量
     *
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }
    
    /**
     * 获取redis操作实例
     *
     * @return MemcachedHandler
     */
    protected function getMemcached()
    {
        if (!$this->memcached) {
            $dataPool = Tiny::getApplication()->getData();
            $this->memcached = $dataPool[$this->dataId];
            if (!$this->memcached instanceof MemcachedHandler) {
                throw new SessionException(sprintf("Class %s is not an instance of %s!", get_class($this->memcached), MemcachedHandler::class));
            }
        }
        return $this->memcached;
    }
}
?>