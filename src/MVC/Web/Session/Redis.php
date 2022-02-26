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

use Tiny\Data\Redis\Redis as RedisHandler;
use Tiny\Tiny;

/**
 * Session后端Redis适配器
 *
 * @package Tiny.MVC.Http.Session
 * @since : 2013-4-13上午02:27:53
 * @final : 2013-4-13上午02:27:53
 */
class Redis implements SessionAdapterInterface
{
    
    /**
     * Redis的data操作实例
     *
     * @var RedisHandler
     */
    protected $redis;
    
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
     * @param array $config 配置
     * @return void
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
     * @param string $savePath 保存路径
     * @param string $sessionName session名称
     * @return void
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }
    
    /**
     * 关闭Session
     *
     * @return void
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
        return $this->getRedis()->get($sessionId);
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
        return $this->getRedis()->set($sessionId, $sessionValue, $this->expires);
    }
    
    /**
     * 注销某个变量
     *
     * @param string $sessionId Session身份标示
     * @return bool
     */
    public function destroy($sessionId)
    {
        return $this->getRedis()->delete($sessionId);
    }
    
    /**
     * 自动回收过期变量
     *
     * @param int $maxlifetime 最大生存时间
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }
    
    /**
     * 获取redis操作实例
     *
     * @return RedisHandler
     */
    protected function getRedis()
    {
        if (!$this->redis) {
            $dataPool = Tiny::getApplication()->getData();
            $this->redis = $dataPool[$this->dataId];
            if (!$this->redis instanceof RedisHandler) {
                throw new SessionException(sprintf("Class %s is not an instance of %s!", get_class($this->redis), RedisHandler::class));
            }
        }
        return $this->redis;
    }
}
?>