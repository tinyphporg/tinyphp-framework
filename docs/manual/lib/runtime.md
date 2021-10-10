Runtime/运行时库
====


类名 | 描述 | 功能 |
-- | -- | -- |
Tiny\Tiny | Runtime代理类 | 自动实例化Runtime并创建Application实例 |  
Tiny\Runtime\Runtime | 运行时类 | 初始化运行时环境参数/运行时缓存/类自动加载/异常处理 |  
Tiny\Runtime\Environment | 运行时环境参数类 | 运行时环境参数设置 |
Tiny\Runtime\Autoloader | 自动类加载 | 自动类加载 |
Tiny\Runtime\RuntimeCache | 运行时缓存 | 基于shmop内存扩展，缓存autoloader/model/controller/configuration等小数据，适应于web环境下 |
Tiny\Runtime\ExceptionHandler | 异常处理句柄  | Tiny接管异常处理 |
Tiny\Runtime\RuntimeException | 运行时异常  | 运行时异常提示 |