应用配置文件
====

3.1 profile.php路径设置
----
> 通过demo/public/index.php入口文件的Tiny\Tiny::createApplication($path,$profile), 调用ApplicationBase __construct($path, $profile = NULL);   
> $path 即APPLICATION_PATH;   
> $profile缺省配置为$path/profile.php;  
> 创建新的Application的实例时，通过引入profile.php管理整个应用组件和MVC流程。   
 
3.2 profile.php的实例化
----
### 在Application实例中
```php
#profile.php的Tiny\Config\Configuration实例
$app->properties;

```

### Controller的引用
```php

#Tiny\Controller\Main

$this->_app->properties;

#or

$this->properties;
```

### 其他
```php
#Model层原则上不允许引用

#View默认没有引用
```

3.3 Debug模式
----


```php
$profile['debug']['enabled'] = TRUE;      /*是否开启调试模式: bool FALSE 不开启 | bool TRUE 开启*/
$profile['timezone'] = 'PRC';             /*设置时区*/
$profile['charset'] = 'utf-8';            /*设置编码*/

#debug
$profile['debug']['param_name'] = 'debug';             /*命令行下  通过--debug开启*/
$profile['debug']['class'] = '\Tiny\MVC\Plugin\Debug'  /*debug输出通过Plugin注册的方式监听事件 可通过此节点自定义新的debug插件*/;
```
> 具体参考 [Debug/调试模式](https://github.com/saasjit/tinyphp/blob/master/docs/manual/debug-004.md)

3.4 异常处理
----
> 通过 exception.eanbled开启框架的异常处理。   
> Debug开启的开发模式下，会输出异常详细信息。   
> Debug关闭的生产环境下，一般使用静默输出，可开启日志记录异常信息的方式。   
```php
$profile['exception']['enabled'] = TRUE;  /*异常处理:bool TRUE 注册Application实例为Tiny\Runtime的异常处理句柄| bool FALSE 默认不处理 */
$profile['exception']['log'] = TRUE;     /*是否以日志方式输出异常信息*/
$profile['exception']['logid'] = 'tinyphp_exception';  /*日志ID*/
```

3.5 Boostrap 引导
----
> 这是除了在入口文件通过$app->setBootrap方式设置引导实例的缺省配置方式。   
> bootstrap.class 可更改为自定义的其他引导类。   
> bootstrap.enabled 可选择关闭引导。  

```php
/**
 * 自动加载引导类
 */
$profile['bootstrap']['enabled'] = TRUE;
$profile['bootstrap']['class'] = '\App\Common\Bootstrap';
```
> 具体可参考 [Bootstrap/引导程序:demo/application/libs/common/Bootstrap.php](https://github.com/saasjit/tinyphp/blob/master/docs/manual/bootstrap-005.md)   

3.6 单文件打包
----
> 这是基于Phar扩展，以插件的方式运行的打包器。   
> build.enabled开启 通过监听命令行输入的--build参数开始打包，可通过build.param_name自定义其他参数名。   
> build.plugin 可更换为其他自定义打包插件。   
> 配置数据和自定义profile.php默认位于APPLICATION_PATH的相对目录下，可更改build.config_path build.profile_path来更换目录。   
```php
/**
 * 打包器设置
 */
$profile['build']['enabled'] = TRUE;  /*不开启时 忽略build打包行为*/
$profile['build']['param_name'] = 'build'; /*--build参数 开启打包工作*/
$profile['build']['plugin'] = '\Tiny\MVC\Plugin\Builder';
$profile['build']['path'] = 'build/builder'; /*打包配置文件夹*/
$profile['build']['config_path'] = 'build/config';  /*打包器的设置文件夹，用来自定义application.config数据*/
$profile['build']['profile_path'] = 'build/profile';  /*打包器的属性文件夹,用来自定义application.properties数据*/
```
具体可参考 [Builder/打包单一可执行文件](https://github.com/saasjit/tinyphp/blob/master/docs/manual/builder-013.md)

3.7 Daemon守护进程
----

> daemon.enabled = TRUE 时开启daemon.plugin设置的daemon插件。
> daemon.plugin开启命令行参数 --daemon|-d 的监听。   
> 指定参数为 --daemon=start|stop|restart 或 -d start|stop|restart
> 更多其他命令行参数可参考[Daemon/守护进程](https://github.com/saasjit/tinyphp/blob/master/docs/manual/daemon-014.md)。
> daemon.id 为缺省情况下的默认ID, 值为 daemon.policys节点对应子数组的KEY。   
> daemon.plugin 为管理daemon进程的插件名，可自定义更改。
> daemon.piddir 设置为运行时的pid文件存放目录，可自定义更改。
> daemon.logdir 日志目录，可自定义更改。   
> daemon.tick 检测子进程退出后重建子进程前等待的时间，防止异常大量创建进程引发崩溃。   
> daemon.polics 的配置数组参数:   
>> id为守护进程标识。   
>> type为worker进程调用的Worker类型。   
>> args数组的controller和action为Worker控制器名称和动作名称。   
>> num为进程数量。   
>> options为type=worker时的参数配置，runmax=1024为每个进程最大运行控制器的动作1024次后退出，tick为该子进程退出后重建前的等待时间。     
>>
```php
/**
 * 守护进程的基本设置
 */
$profile['daemon']['enabled'] = TRUE;
$profile['daemon']['id'] = 'tinyphp-daemon';          /*默认的daemonid*/
$profile['daemon']['plugin'] = '\Tiny\MVC\Plugin\Daemon';
$profile['daemon']['piddir'] = 'runtime/pid/'; /*守护进程pid目录*/
$profile['daemon']['logdir'] = 'runtime/log/'; /*守护进程的日志目录*/
$profile['daemon']['tick'] = 2;                /*检测子进程退出后的tick数 避免异常时大量创建操作系统进程引发崩溃*/

/**
 * 加载指定守护进程的配置参数  type
 *                     worker 运行指定次数退出的worker
 *                     timerworker 定时触发的worker 未实现
 *                     networker 监听各种端口的worker 未实现
*/
$profile['daemon']['policys'] = [
    'tinyphp-daemon' => [
        'workers' => [      //worker子进程配置
            ['id' => 'index', 'type' => 'worker' , 'args' => ['controller' => 'main', 'action' => 'index'], 'num' => 1, 'options' => ['runmax' => 1024, 'tick' => '0.1']],
            ['id' => 'test', 'type' => 'worker' , 'args' => ['controller' => 'main', 'action' => 'test'], 'num' => 10, 'options' => ['runmax' => 1024, 'tick' => '1']]
        ],
        'options' => [],
    ],
];
```
> 更多可参考 [Daemon/守护进程](https://github.com/saasjit/tinyphp/blob/master/docs/manual/daemon-014.md)。

3.8 Config配置
----
> config.enabled = TRUE|FALSE application->getConfig()是否输出Configuration的实例。     
>> controller中调用 $this->config;   
>> model中调用 $this->config;   
>> viewer中调用 $config。   
> config.path 配置文件/文件夹所在的相对路径位置 可自定义更改。  
> config.paths Configuration实例支持多个配置的路径加载，往前覆盖重复数据。  
> config.cache.enabled 是否开启配置的缓存功能, 缓存通过runtimeCache实现。 

```php
/**
 * application配置模块设置
 */
$profile['config']['enabled'] = TRUE;   /* 是否开启默认配置模块 */
$profile['config']['path'] = 'config/'; /* 配置文件相对路径 */
$profile['config']['paths'] = [];       /*可加载多个扩展的配置文件或文件夹路径，必须为绝对或者相对路径 数据可覆盖*/
$profile['config']['cache']['enabled'] = TRUE; /*配置模块缓存设置 提高性能*/
```
更多可参考 [Configuration/配置类](https://github.com/saasjit/tinyphp/blob/master/docs/manual/configuration-012.md)

3.9 Lang配置
----
> lang.enabled = TRUE|FALSE application->getLang()是否输出Lang的实例。   
>> controller中调用 $this->lang;   
>> model中调用 $this->lang;   
>> viewer中调用 $lang。    

> lang.locale = zh_cn 默认语言包名称，默认会影响views/下的模板文件夹。   
> lang.path 语言包配置路径。      
>  lang.cache.enabled 是否开启语言包缓存, 缓存通过runtimeCache实现。    
```php
/**
 * 语言模块设置
 */
$profile['lang']['enabled'] = TRUE;   /*是否开启 */
$profile['lang']['locale'] = 'zh_cn';
$profile['lang']['path'] = 'lang/';   /*存放语言包的目录 */
$profile['lang']['cache']['enabled'] = TRUE; /*配置模块缓存设置 提高性能*/
```
> 更多可参考 [Lang/语言包:demo/application/lang](https://github.com/saasjit/tinyphp/blob/master/docs/manual/lang-006.md)

3.10 Logger配置
----
> log.enabled = TRUE|FALSE application->getLogger()是否输出Logger的实例。   
> log.type = file|syslog file为文件系统记录  syslog是通过php的syslog扩展记录日志。   
> log.path log.type=file时 设置的log日志文件存放路径。   

```php
/**
 * 日志模块设置
 */
$profile['log']['enabled'] = TRUE;
$profile['log']['type'] = 'file';    /*默认可以设置file|syslog 设置类型为file时，需要设置log.path为可写目录路径 */
$profile['log']['path'] = 'runtime/log/';
```
> 更多可参考 [Logger/日志收集:demo/application/runtime/log](https://github.com/saasjit/tinyphp/blob/master/docs/manual/logger-010.md)   

3.11 Data数据源管理
----
> <b>Data管理的数据源可供model|session|cache调用</b>    
> data.enabled 是否开启数据池管理 application->getData()是否输出Data实例。   
> data.charset 默认编码为utf8。    
> data.policys 管理是有的数据源链接。   
>> id data源ID 在model中通过$this->data[id] 调用该数据源的实例。   
>> driver data驱动  支持mysql|mysqli|pdo_mysql|redis|memcache 可自定义扩展其他数据源管理。   
>>  其他参数为该驱动所需的连接参数，根据具体驱动实例需求设置。   
```php
/**
 * 数据模块设置
 * id为 default时，即为默认缓存实例
 *  driver mysql
 *  dirver mysqli
 *  dirver pdo_mysql
 *  driver redis
 *  driver memcache
 */
$profile['data']['enabled'] = TRUE;    /* 是否开启数据池 */
$profile['data']['charset'] = 'utf8';
$profile['data']['policys'] = [
    ['id' => 'default', 'driver' => 'db.mysql_pdo', 'host' => '127.0.0.1', 'port' => '3306', 'user' => 'root', 'password' => '123456', 'dbname' => 'mysql'],
    ['id' => 'redis', 'driver' => 'redis', 'host' => '127.0.0.1', 'port' => '6379' ],
    ['id' => 'redis_cache', 'driver' => 'redis', 'host' => '127.0.0.1', 'port' => '6379', 'servers' => [['host' => '127.0.0.1', 'port' => '6379'],['host' => '127.0.0.1', 'port' => '6379']]],
    ['id' => 'redis_session', 'driver' => 'redis', 'host' => '127.0.0.1', 'port' => '6379'],
    ['id' => 'redis_queue', 'driver' => 'redis', 'host' => '127.0.0.1', 'port' => '6379'],
    ['id' => 'memcached', 'driver' => 'memcached', 'host' => '127.0.0.1', 'port' => '11211']
];
```
> 更多可参考 [Data/数据源](https://github.com/saasjit/tinyphp/blob/master/docs/manual/data-007.md)

3.12 Cache
----

> cache.enabled = TRUE|FALSE application->getCache()是否输出Cache的实例。     
>> controller中调用 $this->cache;   
>> model中调用 $this->cache;   
>> viewer中调用 $cache。   
> cache.lifetime 缺省的缓存时间。 
> cache.filepath 本地文件缓存时的相对缓存路径
> cache.policys 缓存配置策略
>>  id  $this->cache[id] 调用具体配置的缓存实例
>>  driver 缓存驱动，具体类型的缓存类型 file|redis|memcache
>  
```php
/**
 * 缓存模块设置
 * id为 default时，即为默认缓存实例 可以用Cache::getInstance()使用 或者在controller以及Model中 直接以$this->cache使用
 * driver 
 *       driver=file     文件缓存  文件缓存填写相对application的路径，不允许绝对路径
 *       driver=memcache memcache缓存 dataid=data数据池driver=memcache配置ID
 *       driver=redis    Redis缓存    dataid=data数据池driver=redis配置ID
 */
$profile['cache']['enabled'] = TRUE; /* 是否默认开启缓存模块，若不开启，则以下设置无效 */
$profile['cache']['lifetime'] = 3600;
$profile['cache']['filepath'] = 'runtime/cache/'; /*文件缓存方式的缓存相对路径*/
$profile['cache']['policys'] = [
    ['id' => 'default', 'driver' => 'redis', 'lifetime' => 3600, 'dataid' => 'redis_cache'],
    ['id' => 'file', 'driver' => 'file', 'lifetime' => 3600, 'path' => '']
];
```
> 更多可参考 [Cache/缓存:demo/](https://github.com/saasjit/tinyphp/blob/master/docs/manual/cache-008.md)

3.13 Session
----

> session.enabled 是否开启框架内的session管理。   
> session.domain决定SESSIONID的作用域。    
> session.path 决定SESSIONID的作用路径。   
> session.expires  决定SESSIONID的过期时间。   
> driver=redis|memcache 支持redis|memcache两种全局共享的session方式。   
> dataid 配置为driver对应的dataid。    
```php
/**
 * HTTP SESSION设置
 * driver 为空 PHP自身Session
 * driver memcache Memcache
 * driver redis Redis作为Session */
$profile['session']['enabled'] = TRUE;
$profile['session']['domain'] = '';
$profile['session']['path'] = '/';
$profile['session']['expires'] = 36000;
$profile['session']['domain'] = '';
$profile['session']['driver'] = 'redis';
$profile['session']['dataid'] = 'redis_session';
```
> 更多可参考 [Controller/控制器:demo/application/controllers/](https://github.com/saasjit/tinyphp/blob/master/docs/manual/controller-017.md)

3.14 Filter过滤器设置
----
> filter.enabled application->getFilter()是否输出Filter实例。   
> filter.web WEB环境下的过滤器配置，可自定义更换。    
> filter.console console环境下的过滤器配置，可自定义更换。   
> filter.filters 可自定义实现了Tiny\Filter\IFilter接口的filter实例。   
> 主要影响为 controller下的$this->get $this->post $this->param等参数的过滤。
   
```php
/**
 * 过滤器配置
 */
$profile['filter']['enabled'] = TRUE;
$profile['filter']['web'] = '\Tiny\Filter\WebFilter';
$profile['filter']['console'] = '\Tiny\Filter\ConsoleFilter';
$profile['filter']['filters'] = [];
```

3.15 Cookie
----
> Cookie的缺省参数配置
> Cookie在框架中的管理，仅支持Controller的引用 $this->cookie
```php
/**
 * HTTP COOKIE设置
 */
$profile['cookie']['domain'] = '';
$profile['cookie']['path'] = '/';
$profile['cookie']['expires'] = 3600;
$profile['cookie']['prefix'] = '';
$profile['cookie']['encode'] = FALSE;
```
> 更多可参考 [Controller/控制器:demo/application/controllers/](https://github.com/saasjit/tinyphp/blob/master/docs/manual/controller-017.md)

3.16 MVC流程控制
----
> 相关MVC流程的命名空间配置
> controller.default 默认控制器名称    
> controller.param 默认输入的控制器参数名 http://localhost/index.php?c=main   
> controller.namespace web环境下的控制器命名空间   
> controller.console console环境下的控制器命名空间   
> controller.rpc     rpc环境下的控制器命名空间  rpc目前未实现   
>  model.namespace 模型的命名空间设置   
>  action.default 默认的动作名称   
>  action.param 默认的动作输入参数名  http://localhost/index.php?a=index   
> response.formatJsonConfigId。   
>> 在控制器中通过$this->outFormatJSON($status=0)格式化输出JSON响应体时，通过$status 在$this->config寻找status配置节点名的值。   
```php
/**
 * 控制器设置
 */
$profile['controller']['default'] = 'main';
$profile['controller']['param'] = 'c';
$profile['controller']['namespace'] = 'Controller';
$profile['controller']['console'] = 'Controller\Console';
$profile['controller']['rpc'] = 'Controller\RPC';


/**
 * 模型
 */
$profile['model']['namespace'] = 'Model';

/**
 * 动作设置
 */
$profile['action']['default'] = 'index';
$profile['action']['param'] = 'a';

/**
 * response输出JSON时 默认指定的配置ID
 */
$profile['response']['formatJsonConfigId'] = 'status';

/**
 * 视图设置
 * 视图引擎绑定
 * 通过扩展名绑定解析引擎
 * php PHP原生引擎
 * 类型 tpl Smarty模板引擎
 * 类型 htm Template模板引擎
 */
$profile['view']['src']     = 'views/';
$profile['view']['lang']['enabled'] = true;
$profile['view']['cache']   = 'runtime/view/cache/';
$profile['view']['compile'] = 'runtime/view/compile/';
$profile['view']['config']  = 'runtime/view/config/';
$profile['view']['engines'] = [];
$profile['view']['assign'] = [];

/**
 * 路由规则设置
 */
$profile['router']['enabled'] = TRUE; /* 是否开启router */
$profile['router']['routers'] = [];   /*注册自定义的router*/
$profile['router']['rules'] = [
    ['router' => 'pathinfo', 'rule' => ['ext' => '.html'], 'domain' => ''],
    ];

/**
 * 是否开启插件
 */
$profile['plugin']['enabled'] = FALSE;
```
> 更多可参考 [Controller/控制器:demo/application/controllers/](https://github.com/saasjit/tinyphp/blob/master/docs/manual/controller-017.md)   
> [Router/路由器](https://github.com/saasjit/tinyphp/blob/master/docs/manual/router-009.md)     
> [Dispatcher/派发器](https://github.com/saasjit/tinyphp/blob/master/docs/manual/dispatcher-011.md)   
> [Controller/控制器:demo/application/controllers/](https://github.com/saasjit/tinyphp/blob/master/docs/manual/controller-017.md)   
> [Model/模型:demo/application/models](https://github.com/saasjit/tinyphp/blob/master/docs/manual/model-018.md)   
> [Viewer/视图:demo/application/views](https://github.com/saasjit/tinyphp/blob/master/docs/manual/viewer-019.md)   
> [Plugin/插件](https://github.com/saasjit/tinyphp/blob/master/docs/manual/plugin-016.md)   

3.17 application的路径管理和配置
----
> 一般设置为相对APPLICATION_PATH下的相对路径。      
> 单文件打包时，会自动修改该选项。   
> src配置数组全部为相对路径
> path 配置数据，即将profile.php中的对应节点添加APPLICATION_PATH的真实路径前缀。
```php
/**
 *  应用基本设置
 */
$profile['app']['namespace'] = 'App';        /*命名空间*/
$profile['app']['resources'] = 'resource/';  /*资源文件夹*/
$profile['app']['runtime'] = 'runtime/';     /*运行时文件夹*/
$profile['app']['tmp'] = 'runtime/tmp/';     /*临时文件夹*/


/**
 * application的源码设置
 */
$profile['src']['path'] = '';             /*源码路径*/
$profile['src']['global'] = 'libs/global/';       /*全局类*/
$profile['src']['library'] = 'libs/vendor/';       /*外部引入实例库*/
$profile['src']['controller'] = 'controllers/web/'; /*控制类*/
$profile['src']['model'] = 'models/';           /*模型类*/
$profile['src']['console'] = 'controllers/console/';        /*命令行控制类*/
$profile['src']['rpc'] = 'controllers/rpc/';               /*rpc控制类*/
$profile['src']['common'] = 'libs/common/';         /*通用类*/
$profile['src']['view'] = 'views/';             /*视图源码*/


/**
 * 需要添加绝对路径APPLICATION_PATH的配置项
 */
$profile['path'] = [
            'src.path',
            'app.assets',
            'build.path',
            'build.profile_path',
            'build.config_path',
            'config.path',
            'lang.path',
            'log.path',
            'cache.path',
            'view.src',
            'view.cache',
            'view.compile',
            'view.config',
            'src.library',
            'src.global',
            'src.controller',
            'src.console',
            'src.rpc',
            'src.model',
            'src.common',
            'daemon.piddir',
            'daemon.logdir'
];
```

3.18 application下的自动加载管理
----

> autoloader.librarys 配置加载类库
> KEY=VALUE 为命名空间=profile.php中的配置节点的值
> * 为全局命名空间
> src.rpc = $profile[src][rpc];
> autoloader.no_realpath = TRUE|FALSE 是否在$profile[src][rpc]前加上APPLICATION_PATH;

```php
/**
 * 自动加载库的配置
 */

$profile['autoloader']['librarys'] = [
        'App\Controller' => 'src.controller',
        'App\Controller\Console' => 'src.console',
        'App\Controller\Rpc' => 'src.rpc',
        'App\Model' => 'src.model',
        'App\Common' => 'src.common',
        '*' => 'src.global',
];
$profile['autoloader']['no_realpath'] = FALSE;   /*是否替换加载库的路径为真实路径 phar兼容性*/
```
> 更多可参考 [Tiny\Runtime：运行时](https://github.com/saasjit/tinyphp/blob/master/docs/manual/lib/runtime.md)