Tinyphp-framework
====

> 一款简单的、轻量级的，经过日PV十亿级别的生产环境检验的 MVC 框架。    
> 主要应用于分布式的、高并发的生成环境。
> 适应于多人团队协作;    
> 支持多环境和分布式处理；    
> 适用于 Web/Console/RPC等运行环境，包括单一命令行文件打包，多任务的服务端守护进程等。   

> demo/tinyphp：[https://github.com/saasjit/tinyphp.git](https://github.com/saasjit/tinyphp)   
> 中文文档/tinyphp-docs: [https://github.com/saasjit/tinyphp-docs.git](https://github.com/saasjit/tinyphp-docs)   
> UI组件/tinyphp-ui: [https://github.com/saasjit/tinyphp-docs.git](https://github.com/saasjit/tinyphp-docs)  

引导
====
* [运行环境](#运行环境)
    * [开发环境部署，docker](#docker)
    * [生产环境部署，CentOS7X.X86_64](#centos)
 
* [Demo:http://github.com/saasjit/tinyphp.git](http://github.com/saasjit/tinyphp.git)    
    * [tinyphp](#tinyphp) 
* [中文文档:https://github.com/saasjit/tinyphp-docs.git](https://github.com/saasjit/tinyphp-docs)   
  * [语言基础规范](https://github.com/saasjit/tinyphp-docs/tree/master/docs/coding)
  * [SQL设计规范](https://github.com/saasjit/tinyphp-docs/tree/master/docs/sql)
  * [团队编码规范](https://github.com/saasjit/tinyphp-docs/tree/master/docs/team)

* [框架使用手册](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/) 
    * [Index/入口文件:    demo/public/index.php](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/index-001.md)
    * [Application/应用: demo/application/](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/application-002.md)    
    * [Proptrites/应用配置:  demo/application/config/profile.php](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/profile-003.md)
        * [Debug/调试模式](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/debug-004.md)
        * [Bootstrap/引导程序:demo/application/libs/common/Bootstrap.php](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/bootstrap-005.md)
        * [Lang/语言包:demo/application/lang](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lang-006.md)
        * [Data/数据源](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/data-007.md)
        * [Cache/缓存:demo/](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/cache-008.md)
        * [Router/路由器](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/router-009.md)
        * [Logger/日志收集:demo/application/runtime/log](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/logger-010.md)
        * [Dispatcher/派发器](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/dispatcher-011.md)
        * [Configuration/配置类](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/configuration-012.md)
        * [Builder/打包单一可执行文件](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/builder-013.md)
        * [Daemon/守护进程](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/daemon-014.md)
        * [Filter/过滤器](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/filter-015.md)
        * [Plugin/插件](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/plugin-016.md)
    * [Controller/控制器:demo/application/controllers/](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/controller-017.md)
    * [Model/模型:demo/application/models](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/model-018.md)
    * [Viewer/视图:demo/application/views](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/viewer-019.md)
    
* [框架标准库参考](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/)
    * [Tiny：工具包](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/tiny.md)
    * [Tiny\Runtime：运行时](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/runtime.md)
    * [Tiny\Build：打包](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/build.md)
    * [Tiny\Cache：缓存](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/cache.md)
    * [Tiny\Config：配置](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/config.md)
    * [Tiny\Console：命令行](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/console.md)
    * [Tiny\Data：数据层](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/data.md)
    * [Tiny\Filter：过滤器](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/filter.md)   
    * [Tiny\Image：图片处理](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/image.md)
    * [Tiny\Lang：语言包](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/lang.md)
    * [Tiny\Log：日志处理](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/log.md)
    * [Tiny\MVC：MVC](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/mvc.md)
    * [Tiny\Net：网络](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/net.md)
    * [Tiny\String：字符处理](https://github.com/saasjit/tinyphp-docs/blob/master/docs/manual/lib/string.md) 
   
                     


运行环境
====

### centos
> 适应于生产环境，依赖于lnmp-utils。   
> lnmp-utils: Linux(CentOS7X_64) +openresty(nginx)+Mysql+PHP+Redis一键安装包。    
> 项目地址: https://github.com/saasjit/lnmp-utils    

```shell

git clone https://github.com/saasjit/lnmp-utils.git
cd ./lnmp-utils
./install.sh -m tinyphp
cd /data/web/tinyphp
php public/index.php

```

### docker
>  适应于开发环境

```shell

#可更改/data/workspace/tinyphp为自定义IDE工作目录
workspace=/data/workspace/tinyphp

docker pull centos:7

docker run -d -p 80:80 -p 10022:22 -v $workspace:/data/web/tinyphp --name="tinyphp" --hostname="tinyphp" --restart=always centos:7 /sbin/init

docker exec -it tinyphp /bin/bash

#login docker tinyphp

git clone https://github.com/saasjit/lnmp-utils.git
cd ./lnmp-utils
./install.sh -m tinyphp-bootstrap
curl http://127.0.0.1

```

DEMO
====

### tinyphp

```shell

composer create-project saasjit/tinyphp

cd tinyphp

#运行
php public/index.php

#编译
php public/index.php --build

#开启守护进程
php public/index.php -d

#编辑具体配置文件
vi application/config/profile.php
```
