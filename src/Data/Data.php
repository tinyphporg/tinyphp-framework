<?php
/**
 *
 * @copyright (C), 2011-, King.$i
 * @name Data.php
 * @author King
 * @version Beta 1.0
 * @Date Sun Dec 25 23:35:04 CST 2011
 * @Description 数据池管理
 * @Class List:
 *        1.Data 数据池管理类 管理所有的数据源
 * @Function List:
 *           1.
 * @History <author> <time> <version > <desc>
 *          King Sun Dec 25 23:35:04 CST 2011 Beta 1.0 第一次建立该文件
 *          King 2020年3月2日11:31 stable 1.0 审定
 *
 */
namespace Tiny\Data;

use Tiny\Data\Memcached\Memcached;
use Tiny\Data\Redis\Redis;
use Tiny\Data\Db\Db;

/**
 * 数据库模型操作类
 *
 * @package Tiny.Data
 * @since Wed Dec 28 09:09:58 CST 2011
 * @final Wed Dec 28 09:09:58 CST 2011
 *        King 2020年3月2日11:31 stable 1.0 审定
 */
class Data implements \ArrayAccess
{
    
    /**
     * 已经注册的数据源映射表
     *
     * @var array
     */
    protected static $dataSourceDrivers = [
        'db' => Db::class,
        'memcached' => Memcached::class,
        'redis' => Redis::class
    ];
    
    /**
     * 默认使用的Data池 数据源ID
     *
     * @var string
     */
    protected $defaultId = 'default';
    
    /**
     * 数据源配置数组
     *
     * @var array
     */
    protected $dataSources = [];
    
    /**
     * 注册数据源驱动
     *
     * @param string $id 数据源ID
     * @param string $className 数据源操作类名称
     * @return void
     */
    public static function regDataSourceDriver($driverId, $driverClass)
    {
        if (self::$dataSourceDrivers[$driverId]) {
            throw new DataException(sprintf('Faild to register data source: the driver %s already exists', $driverId));
        }
        self::$dataSourceDrivers[$driverId] = $driverClass;
    }
    
    /**
     * 增加一个数据源
     *
     * @param array $policy 数据池配置数组
     * @return void
     */
    public function addDataSource(array $config)
    {
        $id = (string)$config['id'];
        if (!$id) {
            throw new DataException('Failed to add data source: Datasource.config.id is not set!');
        }
        
        // driver
        $driver = (string)$config['driver'];
        if (strpos($driver, '.') > -1) {
            $ds = explode('.', $driver);
            $driver = $ds[0];
            $config['adapter'] = $ds[1];
        }
        
        if (!key_exists($driver, self::$dataSourceDrivers)) {
            throw new DataException(sprintf('Failed to add data source: Data driver %s  is not registered', $driver));
        }
        
        $driverClass = self::$dataSourceDrivers[$driver];
        $config['driver'] = $driver;
        $config['driverClass'] = $driverClass;
        $config['instance'] = null;
        $this->dataSources[$id] = $config;
    }
    
    /**
     * 设置默认的default 数据源操作ID
     *
     * @param string $id 数据操作实例ID
     * @return void
     */
    public function setDefaultId(string $id)
    {
        if (!key_exists($id, $this->dataSources)) {
            throw new DataException(sprintf('Failed to set default data source ID:%s does not exists!', $id));
        }
        $this->defaultId = $id;
    }
    
    /**
     * 获取默认的数据源操作ID
     *
     * @return string
     */
    public function getDefaultId()
    {
        return $this->defaultId;
    }
    
    /**
     * 根据数据策略的ID标识获取一个数据句柄
     *
     * @param string $id Data池的数据源id
     * @return DataSourceInterface
     */
    public function getDataSource(string $id = null)
    {
        if (!$id) {
            $id = $this->defaultId;
        }
        
        if (!key_exists($id, $this->dataSources)) {
            throw new DataException(sprintf('Failed to get data source instance: dataId does not exists!', $id));
        }
        
        $dataSource = $this->dataSources[$id];
        if ($dataSource['instance']) {
            return $dataSource['instance'];
        }
        
        $driverClass = $dataSource['driverClass'];
        unset($dataSource['driver'], $dataSource['id']);
        
        $dataSourceInstance = new $driverClass($dataSource);
        if (!$dataSourceInstance instanceof DataSourceInterface) {
            throw new DataException(sprintf('Failed to get data source instance: %s is not an instance of %s', $driverClass, DataSourceInterface::class));
        }
        $this->dataSources[$id]['instance'] = $dataSourceInstance;
        return $dataSourceInstance;
    }
    
    /**
     * 数组接口之设置
     *
     * @param string $id data标示
     * @param DataSourceInterface $dataSchema 数据源操作类
     * @return void
     */
    public function offsetSet($id, $dataSchema)
    {
        throw new DataException("The data pool does not allow adding data source by ID");
    }
    
    /**
     * 数组接口 获取数据源操作实例
     *
     * @param string $id 数据源ID
     * @return DataSourceInterface
     */
    public function offsetGet($id)
    {
        return $this->getDataSource($id);
    }
    
    /**
     * 数组接口 是否存在数据源ID
     *
     * @param string $id 键
     * @return bool
     */
    public function offsetExists($id)
    {
        return $this->getDataSource($id) ? true : false;
    }
    
    /**
     * 数组接口 移除该值 不允许移除
     *
     * @param string $id 数据源ID
     * @return void
     */
    public function offsetUnset($id)
    {
        throw new DataException("The data pool does not allow the data source to be removed by ID");
    }
    
    /**
     * 代理默认的数据库连接
     *
     * @param string $method 函数名
     * @param array $args 参数数组
     * @return mixed
     */
    public function __call(string $method, array $params)
    {
        return call_user_func_array([
            $this->getDataSource(),
            $method
        ], $params);
    }
}
?>