<?php

use Tiny\Cache\Storager\SingleCache;

/**
 * @Copyright (C), 2011-, King.
 * @Name: Profile.php
 * @Author: King
 * @Version: Beta 1.0
 * @Date: Sat Nov 12 23:16:52 CST 2011
 * @Description:
 *      application的Properties实例所加载的配置文件
 * @History:
 * <author> <time> <version > <desc>
 * King Fri Nov 18 00:20:44 CST 2011 Beta 1.0 第一次建立该文件
 * King 2022-3-12 stable 2.0 修改
 */

$profile = [];

/**
 * 基本设置
 *
 * debug.enabled 是否开启debug模式
 *      false 不开启 适用于生产环境，必须强制关闭
 *      true 开启 适用于dev环境
 *      在控制器/模型中，使用$this->setDebug(false) 手动关闭
 *      其他地方，可通过Tiny::getApplication()->isDebug = false 关闭
 *
 *  timezone
 *      时区 默认为东八区北京时间
 *
 *  charset
 *      输出编码 默认为utf-8
 *
 *  namespace
 *      当前application的应用实例下，所有类的根命名空间
 *      在设置多个项目的应用实例时时，可单独命名区分
 */
$profile['debug']['enabled'] = '{env.APP_DEBUG_ENABLED}';
$profile['timezone'] = 'PRC';
$profile['charset'] = 'utf-8';
$profile['namespace']= 'App';
$profile['path']['root'] = '{env.TINY_ROOT_PATH}';
$profile['path']['public'] = '{env.TINY_PUBLIC_PATH}';               // 入口文件夹
$profile['path']['resources'] = '{env.TINY_RESOURCES_PATH}';         // 资源文件夹
$profile['path']['var'] = '{env.TINY_VAR_PATH}';                     // 运行时文件夹
$profile['path']['vendor'] = '{env.TINY_VENDOR_PATH}';               // 
$profile['path']['bin'] = '{env.TINY_BIN_PATH}';
$profile['path']['cache'] = '{env.TINY_CACHE_PATH}';
$profile['path']['static'] = '{env.TINY_PUBLIC_PATH}static/';        // 静态资源文件夹
$profile['path']['tmp'] = '{env.TINY_VAR_PATH}tmp/';
$profile['path']['log'] = '{env.TINY_LOG_PATH}';
$profile['path']['global'] = '{path.app}librarys/global/';           // 存放全局类的文件夹
$profile['path']['library'] = '{path.app}librarys/';                 // 除了composer外，引入的其他项目的库文件夹
$profile['path']['controller']['web'] = '{path.app}controllers/web/';   // web环境下的控制器类文件夹
$profile['path']['controller']['console'] = '{path.app}controllers/console/';  // 命令行环境下的控制器类文件夹
$profile['path']['controller']['rpc'] = '{path.app}controllers/rpc/';          // rpc模式下的控制器类文件夹
$profile['path']['model'] = '{path.app}models/';                 // 模型类文件夹
$profile['path']['config'] = '{path.app}config/';                // 配置文件夹
$profile['path']['view'] = '{path.app}views/';                   // 存放视图模板的文件夹
$profile['path']['pid'] = '{path.var}pid/';
$profile['path']['event'] = '{path.app}events/';
$profile['path']['common'] = '{path.app}librarys/common/';

/**
 * application的容器设置
 *
 * 主要实现依赖注入
 *
 * 注解注入：@autowired
 *          class的注释中 包括@autowired 即可自动加载依赖并实例化
 *          成员属性/成员函数的注释中，包括@autowired ，即可在实例化时自动运行
 *
 * 参数注入：
 *          构造函数自动解析参数，从容器中获取实例，并注入
 *          调用函数时自动解析参数，从容器中获取实例并注入
 *
 *  container.enabled
 *      当前application实例默认开启容器加载
 *
 *  container.provider_path 应用的容器配置文件路径
 *      array [file|dir] 可设置多个容器路径
 *      string file  设置单个文件为容器配置文件
 *      string dir   设置单个路径为容器配置文件集合
 *
 *  container.autowired
 *      默认启用容器自动注解
 *
 */
$profile['container']['provider_path'] = '{path.app}containers/';
$profile['container']['alias'] = [];
$profile['container']['definitions'] = [];

/**
 * application的异常处理
 *
 * exception.enabled 开启application的异常处理
 *      true 设置application实例为异常处理句柄，监听异常事件并处理
 *      false 通过runtime默认异常处理
 *
 * exception.log 异常日志
 *      true 开启 异常输出到日志中
 *      false 关闭输出
 *
 * exception.logid 默认的异常日志id
 *      如果是文件存储，则保留在runtime/log文件夹下，以logid命名的日志文件中
 */
$profile['exception']['enabled'] = true;
$profile['exception']['log'] = true;
$profile['exception']['logid'] = 'tinyphp_exception';

/**
 * 事件管理
 *
 * EventManager
 *      事件管理器在runtime中初始化，引入到application实例中，，主要应用在异常事件处理，和MVC的控制器事件处理
 *
 * event.enabled
 *      true 开启 false 关闭 则所有插件都受影响
 *
 *  event.listeners 监听器设置
 *      listener 需为实现或者继承了EventLister接口的类
 */
$profile['event']['enabled'] = true;
$profile['event']['listeners'] = [];

/**
 * 调试模式
 *
 * debug.enabled 默认开启调试模式
 *      true 开启 应用于开发环境 | false 关闭 应用于线上环境
 *
 * debug.event_listener
 *      监听事件并处理的调试器事件监听
 *
 * debug.param_name
 *      ConsoleApplication环境下，监听到--debug命令行参数，即开启调试信息输出
 *      web环境下，监听到控制器=debug时，即输出debug调试信息
 *
 * debug.console 仅在WebApplication环境下生效
 *      输出debug信息到浏览器的console控制台
 *
 * debug.cache.enabled
 *  控制在调试模式下，是否进行应用缓存
 *  
 *  debug.clear_cache
 *  清空缓存路径
 *  
 *  debug.split_log
 *  按日期切割路径
 */
// $profile['debug']['enabled'] = true;
$profile['debug']['event_listener'] = \Tiny\MVC\Event\DebugEventListener::class;
$profile['debug']['param_name'] = 'debug';
$profile['debug']['cache']['enabled'] = true;
$profile['debug']['console'] = false;

/**
 * 打包器
 *
 * 仅在命令行环境的ConsoleApplication实例生效
 *
 * builder.enabled 是否开启单文件打包器
 *      true 开启  false 关闭监听
 *
 * builder.param_name 参数名
 *      php public/index --build 即可开启单文件打包
 *
 * builder.event_listener 打包器监听事件类
 *      监听到输入参数  --build，即开始初始化打包器
 *
 * builder.path   打包器的配置文件夹
 *      根据配置文件打包
 *
 * builder.config_path
 *      附加到单文件执行时的application的配置数据
 *
 * builder.profile_path
 *      附加到单文件执行时的application的propertis数据
 *
 */
$profile['builder']['enabled'] = true;
$profile['builder']['param_name'] = 'build';
$profile['builder']['event_listener'] = \Tiny\MVC\Event\BuilderEventListener::class;
$profile['builder']['path'] = '{path.app}build/builder';
$profile['builder']['config_path'] = '{path.app}build/config';
$profile['builder']['profile_path'] = '{path.app}build/profile';

/**
 * 守护进程的基本设置
 *
 * 仅在命令行环境的ConsoleApplication实例生效
 *
 * daemon.enabled
 *      是否开启自动监听Daemon的命令行参数监听
 *
 * daemon.id 默认启动的服务ID
 *      id 即 daemon.policys数组里的key
 *
 * daemon.event_listener Daemon事件监听器
 *      监听D命令行的-d --daemon参数 并实例化Daemon
 *
 *  daemon.piddir
 *      守护进程的PID保存目录
 *
 *  daemon.tick
 *      默认子进程退出后重建的间隔
 *
 *  daemon.daemons 配置服务数组
 *      daemonid => [
 *          workers,子进程配置
 *          options => 附加给当前服务实例的选项
 *      ]
 *      workers的配置: 【
 *          id => worker的身份标识
 *          type => worker worker类型，默认为限定循环执行的子进程模式
 *          dispatcher => [controller,action,module]代理执行worker进程的控制器,动作参数, 模块
 *          num => 子进程的数量
 *          options => [] 附加给worker实例的参数
 *              type = worker:
 *                  options => [
 *                  runmax => 最大运行次数，避免内存占用过多系统阻塞
 *                  tick  => 重建子进程的间隔
 *              ]
 *      】
 */
$profile['daemon']['enabled'] = true;
$profile['daemon']['id'] = 'tinyphp-daemon';
$profile['daemon']['event_listener'] = \Tiny\MVC\Event\DaemonEventListener::class;
$profile['daemon']['piddir'] = '{path.var}pid/';
$profile['daemon']['tick'] = 2;
$profile['daemon']['daemons'] = [];

/**
 * 当前Application实例下的Configuration实例设置
 *
 * config.enabled 是否开启配置
 *      true 开启 | false 关闭
 *
 * config.path 配置文件的相对路径
 *      array [file|dir] 可配置多个路径
 *      string file      单个配置文件路径
 *      string dir       文件夹路径
 *
 * config.cache.enabled 是否缓存配置
 *      开启缓存，将读取所有配置文件并解析后，缓存至本地PHP文件
 *      配置文件内严禁函数，类等命名和操作，否则缓存数据无法解析
 *
 */
$profile['config']['enabled'] = true;
$profile['config']['path'] = '{path.config}';
$profile['config']['cache']['enabled'] = true;

/**
 * Application的语言包设置
 *
 * lang.enabled 开启语言包实例化
 *
 * lang.locale 默认语言包
 *      zh_cn 中文语言包
 *
 *  lang.path 存放语言包配置文件的路径
 *      路径配置同config
 *
 * lang.cache.enabled 开启缓存
 *      开启将所有语言包数据缓存
 */
$profile['lang']['enabled'] = true;           // 是否开启
$profile['lang']['locale'] = 'zh_cn';         // 默认语言包
$profile['lang']['path'] = '{path.app}lang/'; // 存放语言包的目录
$profile['lang']['cache']['enabled'] = true;  // 配置模块缓存设置 提高性能

/**
 * application的日志配置
 *
 * log.enabled 开启日志处理
 *
 * log.wirter 日志写入器
 *      file 写入到本地文件
 *      syslog 通过系统syslog函数写入到系统文件夹
 *      rsyslog 通过rsyslog协议，写入到远程文件夹
 *  log.format 日志文件名的格式
 *      为空
 *      可加入日期格式
 */
$profile['log']['enabled'] = true;
$profile['log']['writer'] = 'file';    /*默认可以设置file|syslog 设置类型为file时，需要设置log.path为可写目录路径 */
$profile['log']['path'] = '{path.log}';
$profile['log']['format'] = '%id%-His';

/**
 * 数据资源池配置
 *
 *  data.enabled 开启数据资源池
 *      true 开启|false 关闭
 *
 *   data.default_id 默认ID
 *      默认调用datasource的ID
 *
 *  data.drivers 驱动数组
 *
 *  data.sources 数据资源池配置
 *       mysql驱动
 *      driver = db.mysqli|db.pdo| [
 *          id => 调用时使用的ID字段
 *          host 通用的远程资源
 *          prot 通用的远程端口
 *          charset utf8mb4 兼容表情包
 *          password 通用密码
 *          dbname 数据库名称
 *      ]
 *
 *      redis驱动
 *      driver = redis [
 *          id => 调用时使用的ID字段
 *          host => 远程host 单独设置的host & prot 会合并到servers内
 *          port => 远程端口
 *          db => 选择的DB Index
 *          servers => [[host => 服务, port => 端口]]
 *      ]
 *
 *      memcache驱动
 *      driver = memcached [
 *          servers => [[host=> 服务地址, port=> 端口]]
 *          persistent_id => 共享实例的ID
 *          options => [选项]
 *      ]
 */
$profile['data']['enabled'] = true;
$profile['data']['charset'] = 'utf8';
$profile['data']['default_id'] = 'default';
$profile['data']['drivers'] = [];
$profile['data']['sources'] = [];

/**
 * Application的缓存设置
 *
 * 支持的存储器类型
 *      file => Tiny\Cache\Storager\File 文件存储
 *      memcached => Tiny\Cache\Storager\Memcached memcache存储
 *      php      => Tiny\Cache\Storager\PHP PHP文件序列化存储
 *      redis => Tiny\Cache\Storager\Redis  Redis存储
 *      SingleCache => Tiny\Cache\Storager\SingleCache 单文件存储 适合小数据快速缓存
 *
 *  cache.enabled 开始缓存
 *      true 开启  | false 关闭
 *
 * cache.ttl 默认的缓存过期时间
 *      ttl 可单独设置
 *
 * cache.dir 默认的本地文件缓存路径
 *      string dir 只可设置为文件夹
 *
 * cache.application_storager
 *      string 当前应用实例的缓存存储器
 *
 * cache.default_id 默认的缓存资源ID
 *      $cache 将缓存实例当缓存调用时所调用的cacheID
 *
 * cache.application
 *      是否对application的lang container config等数据进行缓存
 *
 * cache.storagers 缓存存储器的注册列表
 *      [
 *          key => value
 *          存储器ID => 存储器类全程
 *          'file' => \Tiny\Cache\File::class
 *      ]
 *      添加后，即可在cache.sources节点的storager引用
 *
 *  cache.sources 缓存源
 *      本框架的远程缓存源通过datasource统一调度管理
 *      id => 调用缓存资源的ID
 *      storager = redis [
 *          options => [
 *              ttl => 默认过期时间
 *              dataid => 调用的data sources ID
 *          ]
 *      ]
 *
 *      storager => memcached [
 *          options => [
 *              ttl => 默认的过期时间
 *              dataid => 调用的data source id
 *          ]
 *
 *      ]
 *
 *      storager => file [
 *          options =>
 *      ]
 */
$profile['cache']['enabled'] = true;
$profile['cache']['ttl'] = 3600;
$profile['cache']['dir'] = '{path.cache}';
$profile['cache']['default_id'] = 'default';
$profile['cache']['storagers'] = [];
$profile['cache']['sources'] = [];

/**
 * application的过滤器配置
 *
 * filter.enabled 开启过滤
 *
 * filter.web WEB环境下的过滤器设置
 *      string classname 实现FilterInterface的过滤器
 *      array [filterInterface]
 *
 * filter.console 命令行环境下的过滤器设置
 *      string classname 实现FilterInterface的过滤器
 *      array [filterInterface]
 *
 * filter.filters 通用过滤器设置
 *      array [FilterInterface]
 */
$profile['filter']['enabled'] = true;
$profile['filter']['web'] = \Tiny\Filter\WebFilter::class;
$profile['filter']['console'] = \Tiny\Filter\ConsoleFilter::class;
$profile['filter']['filters'] = [];

/**
 * HTTP SESSION设置
 *
 * 仅在WEB环境下有效
 *
 * session.enabled
 *      开启框架自动代理SESSION处理
 *
 * session.domain
 *      session cookie生效的域名设置
 *
 * session.path
 *      session cookie生效的路径设置
 *
 *  session.expires
 *      SESSION过期时间
 *
 *  session.adapters 添加自定义的SESSION适配器
 *      adapterid 适配器ID
 *      adapterClass 实现了session适配器接口的自定义session adapter class
 *
 *  session.adapter SESSION适配器
 *      redis 以datasource的redis实例作为session适配器
 *      memcache 以datasource的rmemcached实例作为session适配器
 *
 *  session.dataid
 *      根据session.adapter选择对应的data资源实例
 * */

$profile['session']['enabled'] = false;
$profile['session']['domain'] = '';
$profile['session']['path'] = '/';
$profile['session']['expires'] = 36000;
$profile['session']['adapters'] = [];
$profile['session']['adapter'] = '';
$profile['session']['dataid'] = '';

/**
 * HTTP COOKIE设置
 *
 * 仅在web环境下生效
 *
 * cookie.domain
 *      默认的cookie生效域名
 *
 * cookie.path
 *      默认的cookie生效路径
 *
 * cookie.expires
 *      默认的cookie过期时间
 *
 *  cookie.prefix
 *      默认的cookie前缀
 *
 *  cookie.encode
 *      cookie是否编码
 */
$profile['cookie']['domain'] = '';
$profile['cookie']['path'] = '/';
$profile['cookie']['expires'] = 3600;
$profile['cookie']['prefix'] = '';
$profile['cookie']['encode'] = false;


/**
 * Application引导类
 *
 * 通过监听引导事件触发
 *
 * bootstrap.enabled
 *      开启引导
 *
 * bootstrap.event_listener
 *      string 实现Bootstrapevent_listener的类名
 *      array [实现Bootstrapevent_listener的类名]
 *
 */
$profile['bootstrap']['enabled'] = false;
$profile['bootstrap']['event_listener'] = null;

/**
 * Application的路由设置
 *
 * router.enabled 开启路由
 *      true 开启 | false 关闭
 *
 * router.routes 注册自定义的route
 *      [
 *          routeid => route classname
 *      ]
 *
 *  router.rules 注册的路由规则
 *      [
 *          route = pathinfo [
 *              rule => [ext => 扩展名, domain => 适配域名]
 *          ]
 *          route = regex [
 *              rule => [regex => 匹配正则, keys => [匹配正则后替换的键值映射表，$1-9即regex匹配数组的索引值]]
 *          ]
 *      ]
 */
$profile['router']['enabled'] = true;  // 是否开启router
$profile['router']['routes'] = [];     // 注册自定义的route
$profile['router']['rules'] = [
    ['route' => 'pathinfo', 'rule' => ['ext' => '.html' , 'domain' => '*', 'priority' => 0]],
];

/**
 * Application的响应实例配置
 *
 * response.formatJsonConfigId
 *    response格式化输出JSON 默认指定的语言包配置节点名
 *    status => $this->lang['status'];
 */
$profile['response']['formatJsonConfigId'] = 'status';

/**
 * Application的控制器配置
 *
 *  controller.namespace 相对Application命名空间的命名空间配置
 *      default Controller Web环境下的控制器命名空间, 如App的命名空间为\App, 即\App\Controller
 *      console Console\Console 命令行下的相对控制器命名空间
 *      rpc    Controller\Rpc
 *
 *  controllr.src
 *      控制器的源码加载目录
 *
 *  controller.default
 *      默认的控制器名称
 *
 *  controller.param
 *      默认的控制器参数
 *
 * controller.action_default
 *      默认的控制器动作名称
 *
 * controller.action_param
 *      默认的控制器动作参数
 *
 */
$profile['controller']['namespace']['default'] = 'Controller';
$profile['controller']['namespace']['console'] = 'Controller\Console';
$profile['controller']['namepsace']['rpc'] = 'Controller\RPC';
$profile['controller']['src'] = '{path.path}controllers/';
$profile['controller']['default'] = 'main';
$profile['controller']['param'] = 'c';
$profile['controller']['action_default'] = 'index';
$profile['controller']['action_param'] = 'a';

/**
 * Application的模型层设置
 *
 * model.namespace
 *      相对app.namespace下的模型层命名空间  如\App\Model
 *
 * model.src  模型层的存放目录
 */
$profile['model']['namespace'] = 'Model';
$profile['model']['src'] = '{path.app}models/';

/**
 * 视图设置
 *
 *  默认模板解析的扩展名列表
 *      .php PHP原生引擎
 *      .tpl Smarty模板引擎
 *      .htm|.html Template模板引擎
 *
 * view.src
 *      视图模板存放的根目录
 *      example: application/views/
 *
 * template_dirname
 *      视图模板目录下的默认存放子级目录
 *          example: views/default/
 *
 * lang.enabled
 *      是否加载对应的语言包子级目录
 *      example: views/zh_cn/ 查找不到后，去默认模板目录里views/default/寻找
 *
 * view.compile
 *      视图模板编译后的存放目录
 *
 * view.config
 *      视图模板的配置存放目录
 *
 * view.assign
 *      视图模板的预先加载配置数组
 *
 * view.engines 视图引擎配置
 *      engine => 视图模板解析类名
 *      ext => []  可解析的模板文件扩展名数组
 *      config => [] 引擎初始化时的配置
 *
 *      Example: Template引擎的插件配置
 *          engine => \Tiny\MVC\View\Engine\Template:
 *          config => [plugins => [
 *              'plugin' => '\Tiny\MVC\View\Engine\Template\Url' , 'config' => []
 *      ]]
 *
 * view.helper 视图助手配置
 *      helper => classname 助手类名
 *      config => [] 助手初始化时的配置
 *
 *  view.cache.enabled 是否开启视图缓存
 *      默认不开启
 *
 *  view.cache.dir 缓存目录
 *  view.cache.ttl 缓存过期时间
 */
$profile['view']['basedir'] = '{path.view}templates/';
$profile['view']['theme'] = 'default';
$profile['view']['lang'] = true;     //自动加载语言包
$profile['view']['paths'] = [];
$profile['view']['compile'] = '{path.view}compile/';
$profile['view']['cache'] = '{path.view}cache/';
$profile['view']['config']  = '{path.view}config/';
$profile['view']['assign'] = [];

// 引擎和助手配置
$profile['view']['engines'] = [];
$profile['view']['helpers'] = [];

// 部件配置
$profile['view']['widgets'] = [];

/*
 * 视图的全局静态资源配置
 *
 * view.static.basedir 视图静态资源的存储根目录
 *      {static} => $profile['src']['static']
 *
 * view.static.public_path 视图静态资源的公开访问地址
 *      /static/ 当前域名下的绝对路径
 *      http://demo.com/static 可指定域名
 *
 * view.static.engine 是否开启视图解析的模板引擎
 *      当前支持css js 图像文件的自动解析和生成
 *
 * view.static.minsize 静态模板引擎复制文件的最小大小
 *      小于最小大小的，直接注入文件内容
 *      大于最小大小的，在staic目录下生成对应外部文件在html下加载
 *
 * view.static.exts
 *      view.static.engine支持解析的静态资源扩展名
 *
 */

$profile['view']['static']['basedir'] = '{path.static}';
$profile['view']['static']['public_path'] = '/static/';
$profile['view']['static']['engine'] = true;
$profile['view']['static']['minsize'] = 2048;
$profile['view']['static']['exts'] = ['css', 'js','png', 'jpg', 'gif'];

/**
 * 自动加载类配置
 * xc v≈Ω
 * autoloader.namespaces 命名空间加载配置
 *      namespace => properties.path.nodes
 *
 *  autoloader.classes 类文件的加载配置
 *      classname => propertis.path.node
 *
 * autoloader.is_realpath  是否绝对路径加载
 *      true 绝对路径加载
 *      false propertis.path里的路径加载
 */
$profile['autoloader']['namespaces'] = [
    'App' => '{path.library}',
    'App\Controller' => '{path.controller.web}',
    'App\Controller\Console' => '{path.controller.console}',
    'App\Controller\Rpc' => '{path.controller.rpc}',
    'App\Model' => '{path.model}',
    'App\Event' => '{path.event}',
    'App\Common' => '{path.common}',
    '*' => '{path.global}',
];
$profile['autoloader']['classes'] = [];

/**
 * 模块管理
 *
 * module.enabled 是否开启模块
 *      true 开启| false 关闭
 *
 * module.event_listener
 *      监听beginRequest事件的模块管理器
 *
 * module.path 模块搜索并自动加载的目录
 *      string 单个路径
 *      array  多个搜索路径
 *
 * module.cache
 *      true|false 是否缓存模块的加载数据
 *
 * module.disabled_modules 禁止加载的模块列表
 *      array 多个禁止的模块名
 *
 * module.default 默认的模块名
 *      null 没有模块
 *
 * module.param 默认的动态请求传递模块名的参数
 *      string 模块名
 *
 */
$profile['module']['enabled'] = true;
$profile['module']['event_listener'] = \Tiny\MVC\Module\ModuleManager::class;
$profile['module']['path'] = ['{path.app}modules/', '{path.vendor}tinyphporg/'];
$profile['module']['cache'] = true;
$profile['module']['disabled_modules'] = [];
$profile['module']['activate_modules'] = [];
$profile['module']['default'] = '';
$profile['module']['param'] = 'm';

/**
 * 模块的静态公共资源配置
 *
 * module.static.enabled 是否开启静态资源的自动复制
 *      true 开启
 *
 *  module.static.web WEB环境下是否自动开启静态资源复制
 *      true 开启  会影响web下的某些性能
 *
 */
$profile['module']['static']['enabled'] = true;
$profile['module']['static']['web'] = true;

/**
 * tinyphp-ui 前端库设置
 *
 * module.tinyphp-ui.enabled 开启
 *      true 开启前确认是否通过composer/框架加载，引入了tinyphporg/tinyphp-ui模块
 *
 * module.tinyphp-ui.public_path 在前端源码展示的公共路径、
 *
 *      根目录下的绝对路径 /tinyphp-ui
 *      包含域名的绝对路径 比如cdn域名， demo.xxx.com/tinyphp-ui/
 *
 *  module.tinyphp-ui.inject
 *      是否自动将ui库的公共路径，注入到html源码
 *      仅支持engine = template时
 *
 *  module.tinyphp-ui.helper
 *      ui前端库在view注册的助手类
 *      message 提示消息体
 *      pagination 分页
 *
 * module.tinyphp-ui.template_dirname
 *      UI库的视图模板路径
 *
 *  module.tinyphp-ui.dev_enabled 是否开启UI调试
 *      必须在tinyphp-ui 运行npm run dev后开启调试模式
 *
 *  module.tinyphp-ui.dev_public_path
 *      调试库在前端展现的URL  相对于view.public_path的路径
 *
 *  module.tinyphp-ui.dev_event_listener
 *      开启调试后的监听事件类
 *
 *  module.tinyphp-ui.assigns array
 *  预设的配置变量注入到视图模板内
 *  example: ui 即寻找tinyphp-ui.config内的ui节点，与application.config的ui节点合并，并以$ui注入到视图变量
 */
$profile['module']['tinyphp-ui']['enabled'] = true;
$profile['module']['tinyphp-ui']['public_path'] = '/static/tinyphp-ui/';
$profile['module']['tinyphp-ui']['inject'] = true;

// UI前端模块的开发设置 可选
$profile['module']['tinyphp-ui']['dev']['enabled'] = false;
$profile['module']['tinyphp-ui']['dev']['dev_public_path'] = "http://127.0.0.1:8080/";

// 将预设配置的变量注入到视图模板
$profile['module']['tinyphp-ui']['assigns'] = ['ui'];

?>