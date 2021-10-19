Debug
====
> Debug仅在当前Application实例的生命周期中存在。

profile.php中开启
---

```php
```

application实例中开启/关闭
---

```php
Tiny::currentApplication()->setDebug();
```

Debug通过Application的Plugin方式实现
----
```php
Application::getInstance()->regPlugin($debug);

onRouter()
onDispatch();
onException();

```
