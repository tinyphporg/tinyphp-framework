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


### 1.3 入口文件在Nginx .conf里的设置
```
Nginx
```
#### 1.3.1 Nginx 不存在的访问全部指向index.php
```

```
#### 1.3.2 Router为pathinfo模式下，在nginx里面的配置项


#### 1.3.3 RPC运行模式下的Nginx配置项

#### 1.3.4 Nginx 静态文件的前端缓存





     # 1.2.1 入口文件
     
    WEB的入口文件一般文件名为index.php
    一个最简单的入口文件只需要三行代码
 
    示例1-1
    <?php

    /*定义应用程序路径常量*/
    define('APPLICATION_PATH', dirname(__DIR__) . '/App/');
 
   /*加载Tiny框架入口文件*/
   include_once(dirname(dirname(__DIR__)) . '/library/Tiny/Tiny.php');
 
   /*运行MVC应用单一实例*/
   Tiny::createApplication(APPLICATION_PATH . 'Profile.php')->run();
   ?>
 
1.2.2 控制器/视图文件
  简单的Hello World并不需要Model层，默认的控制器名称为Main，动作为Index
  将在App\Controller下建立Main.php文件
  
  示例1-2
  <?php
  #头部注释省略
  namespace App\Controller;
  
  /**
   *@desc 实现HelloWorld的控制器
   *@package App.Controller
   *@since 日期
   *@final  日期
   */
  class Main extends Tiny\MVC\Controller\Controller
  {
         /**
          *@desc 输出HelloWorld的首页
          *@access public
          *@param void
          *@return void
         public function indexAction()
         {
                   $this->parse('index.htm');   //解析视图模板并添加到输出里面
          }
  }   
?>
       示例1-2 控制器文件
 
 示例1-3
  <html>
      <head>
           <title>Hello World!</title>
      </head>
      <body>
           <h1>Hello World!</h1>
      </body>
  </html>
             示例1-3 视图文件
 
 
1.2.3 服务器配置及运行
 以Nginx为例，建立指向www/webroot的站点后
 需要重写URL，将所有不存在的URL全部向框架的入口文件处理。
     
Nginx:
if (!-f $request_filename) 
{
     rewrite ^/(.*)$ /index.php;
}
 
Apache:
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^.*(\.xml|\.css|\.js|\.gif|\.png|\.jpg|\.swf|\.jpeg|\.doc|\.rar|\.ico)$
RewriteRule ^(.*)$ index.php [L]

   
Apache的重写配置为
接下来 敲入http://127.0.0.1/ 就可以看到Hello World
 
1.3 Console模式  
    1.3.1 入口文件
 console的入口文件一般文件名为index.php
  一个最简单的入口文件只需要三行代码
 
    示例1-4
        #!/bin/php
    <?php

    /*定义应用程序路径常量*/
    define('APPLICATION_PATH', dirname(__DIR__) . '/App/');
 
   /*加载Tiny框架入口文件*/
   include_once(dirname(dirname(__DIR__)) . '/library/Tiny/Tiny.php');
 
   /*运行Console应用单一实例*/
   Tiny::createApplication(APPLICATION_PATH . 'Profile.php', 'Main')->run();
   ?>
 
1.2.2 控制器/视图文件
  简单的Hello World并不需要Model层，默认的应用程序名称为Main，入口函数为Main
  将在App\Module下建立Main.php文件
  
  示例1-5
  <?php
  #头部注释省略
  namespace App\Module;
  
  /**
   *@desc 实现HelloWorld的控制器
   *@package App.Controller
   *@since 日期
   *@final  日期
   */
  class Main extends Tiny\Console\Application
  {
         /**
          *@desc 输出HelloWorld的首页
          *@access public
          *@param void
          *@return void
         public function mainAction($argv, $argc)
         {
                   echo "hello World";
          }
  }   
?>
       示例1-5 应用程序文件
 
 
1.2.3 服务器配置及运行
 ./index.php
