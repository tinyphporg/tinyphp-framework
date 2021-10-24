Bootstrap
====

> 引导类是Applicatioan实例在初始化后，run执行MVC流程的第一个动作

### 参考
```php
namespace Tiny\MVC;

class ApplicationBase
{
    public function run()
    {
        $this->_bootstrap();
    }
    ...
}
```


> 实现自定义的Bootstrap必须继承 \Tiny\MVC\Bootstrap\Base
> application->_bootstrap()时,会调用\Tiny\MVC\Bootstrap\Base：：init();
> \Tiny\MVC\Bootstrap\Base：：init() 会调用自定义Boostrap类中所有以init开头的公共函数

----

```php
namespace App\Common;


class Bootstrap extend \Tiny\MVC\Bootstrap\Base
{
    
    // 初始化商业化的私有公共库
    public function initAutoloader()
    {
        // 引入私有的公共库 Saasjit
        $path = TINY_ROOT_PATH . 'lib/saasjit';
        $namespace = 'Saasjit';
        $runtime = \Tiny\Runtime\Runtime::getInstance();
        $runtime->import($path, $namespace);  
    }
    
    // 重设properties的节点data设置
    public function initConfig()
    {
        $data = [];
        $this->application->properties->set('data',$data);
    }
    
    // 注册插件
    
    
    //注册视图引擎
    
    //注册路由
    
    ...
}
```

#### namespace Tiny.MVC.Bootstrap

