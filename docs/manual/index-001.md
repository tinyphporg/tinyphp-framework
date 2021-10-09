第一篇 框架入门
====

第一章 Hello world!
====

一个完整的、使用Tiny框架的应用（），一般包括三个部分，入口文件(index.php)，应用程序集合(application) 及 框架程序集合(Tiny Framework For PHP，以后将简称为Tiny).

1.1 通用程序目录结构
----

lib
     \Tiny
             \docs
             \编码规范
             \SQL规范
             \manual   
              \src
                    \Tiny        
www
       /web
                      /index.php          #WEB目录入口文件
       /console    
                      /index.php          #命令行入口文件
       /rpc        
                      /index.php          #RPC 命令行入口文件
                   
       /application                       #应用程序文件夹
        
                      /assets             #程序内部资源文件夹
                      /config
                                    /profile.php             #应用程序的基本设置文件
                                    /lang                    #程序语言包配置
                                  
                      /controllers                #控制器文件夹
                                    ./            #web控制器文件夹
                                    /console      #命令行控制器文件夹
                                    /rpc          #RPC控制器文件夹

                      /models                     #模型文件夹
                      /plugins                    #插件文件夹

              /global                   #全局性类文件夹
              
              /runtime                    #运行文件夹
                        /cache
                        /log
                        /pid
                        /tmp
                        /view
                               /cache
                               /compile
                               /config
              /views                    #视图模板所在文件夹
              


1.2 WEB应用
---
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
