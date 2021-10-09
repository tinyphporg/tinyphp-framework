第五章 常量
======

常量有两种形式：
`全局常量`:用函数`define()`设置;

`命名空间和类内的常量`： 用关键字`const`设置。
 
常量的优势
----
如果不使用常量，直接在程序中填写数字或字符串，将会有什么麻烦？

      程序的可读性（可理解性）变差。程序员自己会忘记那些数字或字符串是什么意思，用户则更加不知它们从何处来、表示什么。

在程序的很多地方输入同样的数字或字符串，难保不发生书写错误。

如果要修改数字或字符串，则会在很多地方改动，既麻烦又容易出错。


`【规则5-1-1】` 尽量使用含义直观的常量来表示那些将在程序中多次出现的数字或字符串。
例如：
```php    
    define（‘MAX’，   100）;        /* PHP的常量设置  */ 
```

<br>
<br>

5.2 const 与 define的取舍
----

`【规则5-2-1】`在大多数情况下，在MVC程序中只使用const常量而不使用define常量，即const常量完全取代define常量。

<br>

5.3 常量定义规则
----

`【规则5-3-1】`需要对外公开的常量放在入口文件中，不需要对外公开的常量放在文件的头部。为便于管理，可以把不同模块的常量集中存放在一个公共的文件中。

`【规则5-3-2】`如果某一常量与其它常量密切相关，应在定义中包含这种关系，而不应给出一些孤立的值。

例如：
```php
define('RADIUS', 100);

define('DIAMETER', RADIUS * 2);
```

<br>

5.4 类中的常量
----

需在类声明中初始化const数据成员。
```php
class A
{
  …
  const SIZE = 100;   // 错误，企图在类声明中初始化const数据成员

}
```