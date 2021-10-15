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


3.5 Application的目录设置
----
> 一般设置为相对APPLICATION_PATH下的相对路径。   
> 单文件打包时，会自动修改该选项。
```php
$profile['app']['namespace'] = 'App';        /*命名空间*/
$profile['app']['resources'] = 'resource/';  /*资源文件夹*/
$profile['app']['runtime'] = 'runtime/';     /*运行时文件夹*/
$profile['app']['tmp'] = 'runtime/tmp/';     /*临时文件夹*/
```

3.6 Boostrap 引导
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

3.7 单文件打包
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
具体可参考[Builder/打包单一可执行文件](https://github.com/saasjit/tinyphp/blob/master/docs/manual/builder-013.md)

3.8 Daemon守护进程
----
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
