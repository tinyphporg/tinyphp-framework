  TinyPHP For Framework
====
[简介](#简介)

[框架安装](#安装)

[一键搭建lnmp运行环境](#一键搭建lnmp运行环境)

[框架编码规范](#框架开发规范)

[一、PHP编码规范](#一、PHP编码规范)
	
[SQL规范](#SQL规范)


简介
====

一款经过生产环境检验(日PV10亿级)的轻量级PHP框架。

```shell
#支持Web和Console两种模式，单文件入口，自动识别web和cli环境，创建web下/console的application。
php index.php
```

支持Console环境下(主要适应于LINUX CENTOS 7)的Daemon守护进程模式。

```shell
   #实现了经典的Master-Worker模式。
   php index.php -daemon=start -id=zeroaid
   
   #可扩展为TCP服务端程序，定时器，IO异步事件驱动等模式，能够365xx24稳定运行。
   ```

支持一键打包成单文件可执行程序。

```shell
   #编译
   php index.php --build
   
   #运行生成的phar单文件程序
   php tinyd.phar
   ```

框架安装
===

```shell
git clone https://github.com/saasjit/tinyphp-bootstrap.git

cd tinyphp-bootstrap

#兼容composer安装saasjit/tinyphp库
composer install saasjit/tinyphp@dev

#直接git下载
mkdir  lib/ && cd lib
git clone https://github.com/saasjit/zeroai-php.git

#运行
php index.php

#编译
php index.php --build

#开启守护进程
php index.php -d

#具体配置文件
vi application/config/profile.php

```


一键搭建lnmp运行环境
====

lnmp-utils
====

框架所在的生产环境 ,Linux(CentOS7X_64) +openresty(nginx)+Mysql+PHP+Redis一键安装包.

项目地址: https://github.com/saasjit/lnmp-utils.git


CentOS 7x.x86_64/生产环境
----
```shell
git clone https://github.com/saasjit/lnmp-utils.git
cd ./lnmp-utils
./install.sh -m tinyphp-bootstrap
curl http://127.0.0.1
 
```

docker/开发环境
----

```shell

#可自定义IDE工作目录
workspace=/data/workspace/tinyphp-bootstrap

docker pull centos:7

docker run -d -p 80:80 -p 10022:22 -v $workspace:/data/web/tinyphp-bootstrap --name="tinyphp-bootstrap" --hostname="tinyphp-bootstrap" --restart=always centos:7 /sbin/init

docker exec -it tinyphp-bootstrap /bin/bash

#login docker tinyphp-bootstrap

git clone https://github.com/saasjit/lnmp-utils.git
cd ./lnmp-utils
./install.sh -m tinyphp-bootstrap
curl http://127.0.0.1

```

框架开发规范
====

一、PHP编码规范
----

>[第一章 文件结构](https://github.com/saasjit/tinyphp/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/001%E6%96%87%E4%BB%B6%E7%BB%93%E6%9E%84.md)

>[第二章 程序的排版](https://github.com/saasjit/tinyphp/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/002%E7%A8%8B%E5%BA%8F%E7%9A%84%E6%8E%92%E7%89%88.md)

>[第三章 命名规则](https://github.com/saasjit/tinyphp/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/003%E5%91%BD%E5%90%8D%E8%A7%84%E5%88%99.md)

>[第四章 表达式和基本语句](https://github.com/saasjit/tinyphp/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/004%E8%A1%A8%E8%BE%BE%E5%BC%8F%E5%92%8C%E5%9F%BA%E6%9C%AC%E8%AF%AD%E5%8F%A5.md)

>[第五章 常量](https://github.com/tinycn/saasjit/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/005%E5%B8%B8%E9%87%8F.md)

>[第六章 函数设计](https://github.com/saasjit/tinyphp/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/006%E5%87%BD%E6%95%B0%E8%AE%BE%E8%AE%A1.md)

>[第七章 IDE的选择](https://github.com/saasjit/tinyphp/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/007IDE%E7%9A%84%E9%80%89%E6%8B%A9.md)

>[第八章 编码规范的一些示例](https://github.com/saasjit/tinyphp/blob/master/docs/001-%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/008%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83%E7%9A%84%E4%B8%80%E4%BA%9B%E7%A4%BA%E4%BE%8B.md)

<br>
<br>

二、SQL使用规范
----

>[第一章 查询规范](https://github.com/saasjit/tinyphp/blob/master/docs/002-SQL%E8%A7%84%E8%8C%83/001%E6%9F%A5%E8%AF%A2%E8%AF%AD%E5%8F%A5.md)

>[第二章 库和表的规范](https://github.com/saasjit/tinyphp/blob/master/docs/002-SQL%E8%A7%84%E8%8C%83/002%E5%BA%93%E5%92%8C%E8%A1%A8%E7%9A%84%E8%A7%84%E8%8C%83.md)

>[第三章 数据库设计原则](https://github.com/saasjit/tinyphp/blob/master/docs/002-SQL%E8%A7%84%E8%8C%83/003%E6%95%B0%E6%8D%AE%E5%BA%93%E8%AE%BE%E8%AE%A1%E5%8E%9F%E5%88%99.md)

>[第四章 数据库优化原则](https://github.com/saasjit/tinyphp/blob/master/docs/002-SQL%E8%A7%84%E8%8C%83/004%E6%95%B0%E6%8D%AE%E5%BA%93%E4%BC%98%E5%8C%96%E5%8E%9F%E5%88%99.md)

<br>
<br>

三、框架使用手册
----
>[第一章 框架入门](https://github.com/saasjit/tinyphp/blob/master/manual/001框架入门.md)