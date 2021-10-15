Application
====

1.1 Application特性
----
> 作用域和保存时间是在整个应用程序的生命期。   
> WEB应用的生命周期服从PHP-FPM的FastCGI协议, 即用户每次访问的开始/结束会创建/销毁Application示例。  
> 应用于整个应用程序/所有用户。    
> Application的生命周期依据配置文件profile.php的配置选项创建和销毁。   
> Application在Tiny\Runtime\Runtime唯一实例上创建和销毁，同时管理整个MVC流程各个功能组件的按需加载。      

1.2 Application的实例化
----

### ApplicationBase
> 具体参考可见 [Tiny/MVC](https://github.com/saasjit/tinyphp/blob/master/docs/manual/lib/mvc.md)
### WebApplication 
> 具体参考可见 [Tiny/MVC](https://github.com/saasjit/tinyphp/blob/master/docs/manual/lib/mvc.md)
### ConsoleApplication
> 具体参考可见 [Tiny/MVC](https://github.com/saasjit/tinyphp/blob/master/docs/manual/lib/mvc.md)

1.3 Application的MVC整体流程
----

> 待完善

1.4 Application 的目录规划原则
----
> 功能文件夹一律小写，并以复数形式规范命名。   
> 存放类文件的命名空间文件夹一律依照命名规范创建。   
> 需要写的文件一律放置于application/runtime下，仅在runtime设置写权限。   

