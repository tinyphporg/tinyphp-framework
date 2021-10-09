第八章  编码规范的一些示例
====

示例8-1 完整的类文件
----
```php
<?php
/**
 * @Copyright (C), 2011-, King.  
 * @Name Example.php   #文件名需与类名相同
 * @Author King #此处写上作者大名，是开发者的签名，也可以在第一时间追查出BUG出自何人之手。
 * @Version Beta 1.1  #当前的版本号
 * @Date: 2012-2-2    #创建的日期
 * @Description          #本文件内的一些简短介绍
 * @Class List
 * 1. Example  示范例子   #将本文件夹内的类做简短的功能介绍和定位
 * @Function List
 * 1.                   
 * @History    #记录该类所有的修改历史
 * <author> <time> <version > <desc>    
 * King 2012-2-2 Beta 1.0 第一次建立该文件
  * King  2013-2-2 Beta1.1 修改文件，增加函数out       
 */
namespace Tiny;        #命名空间需要放置在所有代码前面，declare除外。


/**
*   #类的简短介绍
*
* @example #类的使用示例介绍
* @package  #所属命名空间或者包内
* @since      #起始时间
* @final       #最终修改时间
*/
class Example
{
    /**
     *  防暴力破解的加密KEY
     *
     * @var string 默认的字符串
     */
     private static $_key = 'TINY';
    
    /**
     * 解密函数
     *
     * @param string $string 需要输出的字符
     * @return string
     */
    public static function out($string = NULL)
    {
        return (is_null($string) ? self::$_key : $string);
    }
    
?>  
```
为了代码的完整性，需要闭合PHP标签

示例8-2 循环的一些示例
----
```php
for ($i = 0; $i < 100; $i++)
{
     ……;
}
```
