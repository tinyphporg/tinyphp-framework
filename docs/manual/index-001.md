入口文件
====

1.0 Hello world!
----

> 一个完整的Tiny框架应用，将包括三个部分:   
* 入口文件(demo/public/index.php);   
* 应用程序集合/application(demo/application/);   
* 框架的标准库集合(Tiny Framework For PHP，下文将简称为Tiny)(src/Tiny)    


1.1 通用程序目录结构
----

```
demo/   
    application/
    public/ 
docs/
    manual/
    coding/
    sql/
    team/
src/
    Tiny/
    
tools/
```

1.2 入口文件实例
----
```php
/* 项目根目录 */
define('TINY_ROOT_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);

/* 加载Tiny标准库*/
define('TINY_LIBRARY_FILE', TINY_ROOT_PATH . '/src/Tiny.php');
include_once TINY_LIBRARY_FILE;

/* 自动加载composer库 */
define('TINY_COMPOSER_FILE', TINY_ROOT_PATH . '/vendor/autoload.php');
if (is_file(TINY_COMPOSER_FILE)) 
{
    include_once TINY_COMPOSER_FILE;
}

/* 设置application主目录的常量; 该常量必须设置 
*  Application run 自动识别web/console模式
*  Profile.php 为应用配置文件
*  ->setBootsrap(new \App\Common\Bootstrap()) 设置自定义引导类
*  ->regPlugin(new \App\Common\Plugin()) 注册自定义插件
*  ->run() Application运行
*/
define('APPLICATION_PATH', dirname(__DIR__) . '/application/');
\Tiny\Tiny::createApplication(APPLICATION_PATH, APPLICATION_PATH . 'config/profile.php')->run();
```

#### 1.2.1 设置运行时环境参数  
```php
...
    include_once TINY_COMPOSER_FILE;
}

/* 设置是否开启运行时缓存，设置缓存内存大小参数 */ //运行时缓存仅在WEB/RPC模式下，Linux生产环境，安装了shmop内存扩展时生效
\Tiny\Tiny::setENV([  
    'RUNTIME_CACHE_ENABLED' => TRUE,  //默认开启 FALSE为关闭
    'RUNTIME_CACHE_TTL' => 60,    // 缓存时长 默认60s
    'RUNTIME_CACHE_MEMORY_MIN' => 1048576, //最小共享内存 1M
    'RUNTIME_CACHE_MEMORY_MAX' => 104857600, //最大共享内存 100M
    'RUNTIME_CACHE_MEMORY' => 10485760  //共享内存 10M
]);
 
/* 设置application主目录的常量; 该常量必须设置 
*  Application run 自动识别web/console模式
*  Profile.php 为应用配置文件
*  ->setBootsrap(new \App\Common\Bootstrap()) 设置自定义引导类
*  ->regPlugin(new \App\Common\Plugin()) 注册自定义插件
*  ->run() Application运行
*/
define('APPLICATION_PATH', dirname(__DIR__) . '/application/');
\Tiny\Tiny::createApplication(APPLICATION_PATH, APPLICATION_PATH . 'config/profile.php')->run();
```
#### 1.2.2 入口文件设置自定义引导类  
```php
...
/* 设置application主目录的常量; 该常量必须设置 
*  Application run 自动识别web/console模式
*  Profile.php 为应用配置文件
*  ->setBootsrap(new \App\Common\Bootstrap()) 设置自定义引导类
*  ->regPlugin(new \App\Common\Plugin()) 注册自定义插件
*  ->run() Application运行
*/
define('APPLICATION_PATH', dirname(__DIR__) . '/application/');
$app = \Tiny\Tiny::createApplication(APPLICATION_PATH, APPLICATION_PATH . 'config/profile.php');

/* 
* 自动加载Application目录下的类，需先创建Application实例;
* Bootstrap 必须继承 Tiny\MVC\Bootstrap\Base;
* Bootstrap设置时会替换已存在继承Tiny\MVC\Bootstrap\Base的其他实例；
* Boostrap实例在Application生命周期内仅运行一次。
*/
$bootstrap = new App\Common\Bootstrap(); 
$app->setBootstrap($bootstrap)->run();
```
#### 1.2.3 注册自定义插件
```php
...
/* 设置application主目录的常量; 该常量必须设置 
*  Application run 自动识别web/console模式
*  Profile.php 为应用配置文件
*  ->setBootsrap(new \App\Common\Bootstrap()) 设置自定义引导类
*  ->regPlugin(new \App\Common\Plugin()) 注册自定义插件
*  ->run() Application运行
*/
define('APPLICATION_PATH', dirname(__DIR__) . '/application/');
$app = \Tiny\Tiny::createApplication(APPLICATION_PATH, APPLICATION_PATH . 'config/profile.php');

/* 
* 自动加载Application目录下的类，需先创建Application实例;
* Plugin必须实现接口类Tiny\MVC\Plugin\IPlugin
* Plugin实例可注册多个，在Application生命周期内通过hooks函数多次触发
*/
$plugin = new App\Common\Plugin(); 
$app->regPlugin($plugin)->run();
```

#### 1.2.4 必须的常量
<b>APPLICATION_PATH</b> 定义为application程序集的文件夹路径，必须设置;

#### 1.2.5 参考标准库
> [Tiny\Runtime:运行时标准库](https://github.com/saasjit/tinyphp/blob/master/docs/manual/lib/runtime.md)  
> [Tiny\MVC:MVC库](https://github.com/saasjit/tinyphp/blob/master/docs/manual/lib/mvc.md)  


### 1.3 在不同运行环境下的入口文件使用

#### 1.3.1 Web环境
* 动态路由  
> 参数c为控制器名称,缺省为main,参数c和缺省控制器可在profile.php中的controller节点修改   
> 参数a为动作名称，缺省为index,参数a和缺省动作名可在profile.php中的action节点修改   
> Main控制器 即为调用 application/controllers/web/Main下的控制器类   
> 具体配置可参考: [Proptrites/应用配置:  demo/application/config/profile.php](https://github.com/saasjit/tinyphp/blob/master/docs/manual/profile-003.md)   
```shell
#以上部署在正确可运行的环境
curl "http://localhost/index.php?c=main&a=index"
```

* 伪静态路由   
> 需在profile.php内 router.enable  = TRUE;   
> 并配置对应域名下的router.rules为router.pathinfo   
> 具体配置可参考: [Proptrites/应用配置:  demo/application/config/profile.php](https://github.com/saasjit/tinyphp/blob/master/docs/manual/profile-003.md)    



#### 1.3.2 Console
* 命令行模式
```php
#缺省设置控制器和动作
php demo/public/index.php main/index

#长参输入
php demo/public/index.php --c=main --a=index

#短参输入 
php demo/public/index.php -c main -a index -h -x=2
```


* 服务端服务  
> 示例仅支持CentOS下的service/systemctl方式，除示例外也可自行编写脚本
```shell
cp tools/tiny-daemon.sh /etc/init.d/
chkconfig --level 345 tiny-daemon on
#修改tiny-daemon.sh文件夹中的SERVICE_INDEX_FILE 为正确的入口文件地址
service tiny-daemon start
service tiny-daemon stop
````
* --id 为profile.php的daemon.policys对应配置节点,缺省为daemon.id配置的默认节点 
* -d --daemon=start/stop 控制守护进程开启/关闭
> 具体配置可参考: [Proptrites/应用配置:  demo/application/config/profile.php](https://github.com/saasjit/tinyphp/blob/master/docs/manual/profile-003.md) 
```php
#缺省默认配置
php demo/public/index.php -d

#开启
php demo/public/index.php --id=tinyd -d

#关闭
php demo/public/index.php --id=tinyd -d stop

```
### 1.4 入口文件在Nginx .conf里的设置

#### 1.4.1 Nginx 不存在的访问全部指向index.php
```
location /
{
    try_files $uri $uri /index.php$is_args$args;
}

location ~ .*\.php(/.*)?
{
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_index index.php;
    
    #支持PHP中$_SERVER[pathinfo]显示,不影响框架路由正常工作
    fastcgi_split_path_info ^(.+\.php)(/.+)$; 
    fastcgi_param PATH_INFO $fastcgi_path_info; 
    fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;    
    
    include fastcgi.conf;
}

```


#### 1.4.3 静态文件的前端缓存配置
```
location ~ .*\.(gif|jpg|jpeg|png|bmp|swf)$
{
    expires      30d;
}

location ~ .*\.(js|css)?$
{
    expires      12h;
}
```
