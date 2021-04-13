<?php
return
    [
    'name' => 'tinyphp-demo',
    'header_php_env' => TRUE,  //是否在打包文件首行添加php运行环境标识 #!/usr/bin/php
    'imports' => [], //引用其他路径的库 KEY/命名空间 => VALIE/加载路径
    'attachments' => [
        ['config/app', APPLICATION_PATH . 'config/app'],
    ], //解压到本地路径的附件
];
?>