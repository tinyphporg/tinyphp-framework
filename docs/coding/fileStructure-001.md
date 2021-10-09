文件结构
=======

概述
----

在PHP文件里，承载内容的文件主要有五种:`包文件`、`类文件`、`函数文件`、及`配置文件`，`程序执行文件`。

`包文件(package):` 每个文件包含一个或多个命名空间，每个命名空间里有多个常量、函数或者类。

包(package)文件以包的名称命名，首字母大写，驼峰方式命名

比如包Tiny.Config  文件名为Tiny\Config.php

`类文件:` 应以一文件一类的形式编写，文件名与类名保持一致，命名与类命名规则保持一致，即驼峰结构，每个单词首字母都需大写，例: PublicController.php。

`函数文件:` 如果存在多个同系列的函数，需采取相同的前缀或后缀。

例：
```php
 function  getCookie($key);

 function setCookie($key, $value);
```

`配置文件:` 文件名需与返回数组名保持一致，比如:
```php
$default = include './default.php'。
```
 

1.1 版权和版本声明
----
版权和版本的声明位于头文件和定义文件的开头（参见示例1-1），主要内容有：

（1）版权信息。

（2）文件名称，标识符，摘要。

（3）当前版本号，作者/修改者，完成日期。

（4）版本历史信息。

 

示例 1-1:
```php
/**

*

* @Copyright (C), 2011-2012, XX公司, King@tinycn.com

* @Name [文件名]

* @Author [coder name]

* @Version Beta 1.0

* @Date Sun Dec 25 23:35:04 CST 2011

* @Description [填写文件内容简介]

* @Class List:

*       1.  [填写Class列表简介]

*  @Function List:

*   1.    [填写Function列表简介]

*  @History

*      <author>    <time>                        <version >   <desc>

*       [coder]      当前日期时间，以GMT标准格式填写           第一次建立该文件

*/
```
 

 

1.2 文件结构
----
定义文件有三部分内容：

定义文件开头处的版权和版本声明（参见示例1-1）。

对一些对象或函数库文件的引用。

程序的实现体（包括数据和代码)。

假设定义文件的名称为 PublicController.php，定义文件的结构参见示例1-2。

 示例 1-2:
```php
  <?php
  
  // 版权和版本声明见示例1-1，此处省略。
  
  namespace …;  //命名空间
  //引入的类  
  use …; 

 // 函数的实现体
 function get(…)
 {
    …
 }

 // 类的实现体
 protected class Main
 {
 
  public function getName(…)
  {
    …
  }

  protected function _get(…)
  {
    …
  } 
 
  // 私有成员函数的实现体
  protected class Main
  {
     …
  }
}
 ?>
```
