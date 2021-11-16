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

use Tiny\Data\Memcached\Memcached as MemcachedSchema;
use Tiny\Tiny;

/**
 * Session后端Redis适配器
 *
 * @package Tiny.MVC.Http.Session
 * @since : 2013-4-13上午02:27:53
 * @final : 2013-4-13上午02:27:53
 */
class Memcached implements ISession
{

    /**
     * Redis的data操作实例
     *
     * @var MemcachedSchema
     */
    protected $_schema;

    /**
     * 默认的服务器缓存策略
     *
     * @var array
     */
    protected $_policy = [
        'lifetime' => 3600
    ];

    /**
     * 初始化构造函数
     *
     * @param array $policy
     *        配置
     * @return void
     */
    function __construct(array $policy = [])
    {
        $this->_policy = array_merge($this->_policy, $policy);
    }

    /**
     * 打开Session
     *
     * @return TRUE
     */
    public function open()
    {
        return TRUE;
    }

    /**
     * 关闭Session
     *
     * @return void
     */
    public function close()
    {
        return TRUE;
    }

    /**
     * 读Session
     *
     * @param string $sessionId
     *        Session身份标示
     * @return string
     */
    public function read($sessionId)
    {
        return $this->_getSchema()->get($sessionId);
    }

    /**
     * 写Session
     *
     * @param string $sessionId
     *        SessionID标示
     * @param string $sessionValue
     *        Session值
     * @return bool
     */
    public function write($sessionId, $sessionValue)
    {
        return $this->_getSchema()->set($sessionId, $sessionValue, $this->_policy['lifetime']);
    }

    /**
     * 注销某个变量
     *
     * @param string $sessionId
     *        Session身份标示
     * @return bool
     */
    public function destroy($sessionId)
    {
        return $this->_getSchema()->delete($sessionId);
    }

    /**
     * 自动回收过期变量
     *
     * @return bool
     */
    public function gc()
    {
        return TRUE;
    }

    /**
     * 获取redis操作实例
     *
     * @return MemcachedSchema
     */
    protected function _getSchema()
    {
        if ($this->_schema)
        {
            return $this->_schema;
        }
        $data = Tiny::getApplication()->getData();
        $dataId = $this->_policy['dataid'];
        $schema = $data[$dataId];
        if (!$schema instanceof MemcachedSchema)
        {
            throw new SessionException(sprintf("dataid:%s不是Tiny\Data\Memcached\Memcached的实例", $dataId));
        }
        $this->_schema = $schema;
        return $schema;
    }
}
?>