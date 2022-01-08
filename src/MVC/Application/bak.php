class PropertiesException extends \Exception
{
    
}
class PropertiesDefinition implements DefinitionInterface, SelfResolvingDefinition
{
    
    const DEFINITION_NAME_ALLOW_LIST = ['cache', 'data', 'config', 'lang', 'view'];
    
    /**
     * 应用层 运行时缓存KEY
     * 
     * @var array
     */
    const RUNTIME_CACHE_KEYS = [
        'CONFIG' => 'app.config',
        'LANG' => 'app.lang',
        'MODEL' => 'app.model'
    ];
    
    /**
     * properties 实例
     *
     * @var Properties
     */
    protected $properties;

    protected $name;
    
    /**
     * 应用缓存
     * 
     * @var RuntimeCacheItem
     */
    protected $appCache;
    
    public function __construct(Properties $properties)
    {
        $this->properties = $properties;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function resolve(ContainerInterface $container)
    {
           if (!$this->appCache)
           {
               $this->appCache = $container->get('applicationCache');
           }
           
            switch ($this->name)
            {
                case 'cache':
                    return $this->createCacheInstance();
                case 'data':
                    return $this->createDataInstance();
                case 'config':
                    return $this->createConfigInstance();
                case 'lang':
                    return $this->createLangInstance();
                case 'view':
                    return $this->createViewInstance($container);
                default:
                    return false;
            }
    }

    public function isResolvable(ContainerInterface $container): bool
    {
        return true;
    }

    /**
     * 根据配置创建缓存实例
     * 
     * @return Cache|NULL
     */
    protected function createCacheInstance(): ?Cache
    {
        
        $config = $this->properties['cache'];
        if (! $config['enabled'])
        {
            return null;
        }
        
        // 缓存自定义适配器
        $adapters = (array)$config['storage']['adapters'] ?: [];
        foreach ($adapters as $id => $className)
        {
            Cache::regStorageAdapter($id, $className);
        }
        
        $ttl = (int)$config['ttl'] ?: 60;
        $storageId = (string)$config['storage']['id'] ?: 'default';
        $storagePath = (string)$config['storage']['path'];
        
        $cacheInstance = new Cache();
        $cacheInstance->setDefaultStorageId($storageId);
        $cacheInstance->setDefaultStoragePath($storagePath);
        $cacheInstance->setDefaultTtl($ttl);
        
        $storageConfig = ($config['storage']['config']) ?: [];
        foreach ($storageConfig as $scfg)
        {   
            $options = (array)$scfg['options'] ?: [];
            $cacheInstance->addStorageAdapter($scfg['id'], $scfg['storage'], $options);
        }
        return $cacheInstance;
    }
    
    /**
     * 根据配置创建配置实例
     * 
     * @throws ApplicationException
     * @return \Tiny\Config\Configuration
     */
    protected function createConfigInstance()
    {

        $config = $this->properties['config'];
        if (!$config['enabled'])
        {
            throw new ApplicationException("properties.config.enabled is false!");
        }
        if (!$config['path'])
        {
            throw new ApplicationException("properties.config.path is not allow null!");
        }
        $configInstance = new Configuration($config['path']);
        if ($this->properties['debug.enabled'] || !$config['cache']['enabled'])
        {
            return $configInstance;
        }
        
        $cacheData = $this->getConfigDataFromRuntimeCache();
        if ($cacheData && is_array($cacheData))
        {
            $configInstance->setData($cacheData);
        }
        else
        {
            $data = $configInstance->get();
            $this->saveConfigDataToRuntimeCache($data);
        }
        return $configInstance;
    }
    
    /**
     * 根据配置创建DATA实例
     * 
     * @return Data|NULL
     */
    protected function createDataInstance(): ?Data
    {
        $config = $this->properties['data'];
        if (!$config['enabled'])
        {
            return null;
        }
        
        $config['drivers'] = $config['drivers'] ?: [];
        foreach ($config['drivers'] as $id => $className)
        {
            Data::regDriver($id, $className);
        }
    
        $config['policys'] = $config['policys'] ?: [];
        $config['charset'] = $config['charset'] ?: 'utf8';
        
        $dataInstance = Data::getInstance();
        foreach ($config['policys'] as $policy)
        {
            $policy['def_charset'] = $config['charset'];
            $dataInstance->addPolicy($policy);
        }
        return $dataInstance;
    }
    
    /**
     * 获取语言操作对象
     *
     * @param void
     * @return Lang
     */
    protected function createLangInstance()
    {
        $config = $this->properties['lang'];
        if (!$config['enabled'])
        {
            throw new ApplicationException("properties.lang.enabled is false!");
        }
        $langInstance = Lang::getInstance();
        $langInstance->setLocale($config['locale'])->setPath($config['path']);
        if ($this->properties['debug.enabled']  || !$config['cache']['enabled'])
        {
          return $langInstance;
        }
        
        $langData = $this->getLangDataFromRuntimeCache();
        if ($langData && is_array($langData))
        {
            $langInstance->setData($langData);
        }
        else
        {
            $langData = $langInstance->getData();
            $this->saveLangDataToRuntimeCache($langData);
        }
        return $langInstance;
    }
    
    /**
     * 获取视图类型
     *
     * @return View
     */
    protected function createViewInstance(ContainerInterface $container)
    {

    }
    /**
     * 从运行时缓存获取语言包配置数据
     *
     * @return data|FALSE
     */
    protected function getLangDataFromRuntimeCache()
    {
        return $this->getDataFromRuntimeCache(self::RUNTIME_CACHE_KEYS['LANG']);
    }
    
    /**
     * 保存语言包配置数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function saveLangDataToRuntimeCache($data)
    {
        return $this->saveDataToRuntimeCache(self::RUNTIME_CACHE_KEYS['LANG'], $data);
    }
    
    /**
     * 从运行时缓存获取配置数据
     *
     * @return data|FALSE
     */
    protected function getConfigDataFromRuntimeCache()
    {
        return $this->getDataFromRuntimeCache(self::RUNTIME_CACHE_KEYS['CONFIG']);
    }
    
    /**
     * 保存配置数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function saveConfigDataToRuntimeCache($data)
    {
        return $this->saveDataToRuntimeCache(self::RUNTIME_CACHE_KEYS['CONFIG'], $data);
    }
    
    /**
     * 从运行时缓存获取数据
     *
     * @return data|FALSE
     */
    protected function getDataFromRuntimeCache($key)
    {
        if (!$this->appCache)
        {
            return FALSE;
        }
        $data = $this->appCache->get($key);
        if (!$data || !is_array($data))
        {
            return FALSE;
        }
        return $data;
    }
    
    /**
     * 保存数据到运行时缓存
     *
     * @param array $data
     * @return boolean
     */
    protected function saveDataToRuntimeCache($key, $data)
    {
        if (!$this->appCache)
        {
            return FALSE;
        }
        return $this->appCache->set($key, $data);
    }
}