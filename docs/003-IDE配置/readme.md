1.IDE配置
====


    统一使用Zend Studio为团队协作编辑器。

1.1 安装主题插件
----
点击Help | Install New Software  填写Work with为[http://eclipse-color-theme.github.com/update/](http://eclipse-color-theme.github.com/update/) ，点击Add，选中Eclipse Color Theme及其子项，其他默认，然后点击Next,最后完成。

1.2 选择主题
---

    菜单Window | Preferences | General | Appearance | Color Theme选项

<br>
<br>

1.3 安装注释模板
----
> 文件头部注释 [templates_header.xml](https://github.com/tinycn/tinyphp/blob/master/docs/%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/zend/templates_header.xml)

> 类注释 [templates_class.xml](https://github.com/tinycn/tinyphp/blob/master/docs/%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/zend/templates_class.xml)

> 函数或成员方法注释[templates_func.xml](https://github.com/tinycn/tinyphp/blob/master/docs/%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/zend/templates_func.xml)

> 变量注释 [templates_var.xml](https://github.com/tinycn/tinyphp/blob/master/docs/%E7%BC%96%E7%A0%81%E8%A7%84%E8%8C%83/zend/templates_var.xml)

        菜单 Window | Preferences |PHP | Editor | Templates | Improt 可导入以上注释
  
  使用：
  
        代码提示中，敲出header | class | func | var 即可添加类注释模板。
           

1.4 安装代码风格
---
> [tiny-style.xml](https://github.com/tinycn/tinyphp/blob/master/docs/003-IDE%E9%85%8D%E7%BD%AE/tiny-style.xml)

        菜单 Window | Preferences |PHP | Editor | Code Style| Formatter| Improt 可导入以上注释
 使用：
 
        右键菜单 | Source | Format  或者 按快捷键 Ctrl + Shift + F
 
 
1.5 安装SVN插件
---
