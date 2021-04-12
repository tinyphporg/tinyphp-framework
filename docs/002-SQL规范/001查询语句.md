第一章 查询语句
====

1.1查询语句规范
----
  `【规则1-1.1】` 所有SQL关键字，全部使用大写，比如SELECT INSERT UPDATE WHERE 等.
  例：
  ```sql
  SELECT id FROM t WHERE id = 1
  ```
  `【规则1-1.2】`非必须情况，禁止使用SQL自身函数。
  
  `【规则1-1.3】` 取一行数据时，SQL语句后面，必须加 LIMIT 1语句。
  
  `【规则1-1.4】` 任何查询语句，都必须考虑LIMIT 分页，禁止不带 LIMIT 。
  

1.2 避免使用全表扫描,尽量利用索引
----
  `【规则1-2.1】` 避免在 WHERE 子句中使用!=或<>操作符。
  ```sql
 SELECT id FROM t WHERE name != ''
  ```
  
  `【规则1-2.2】` 避免在WHERE字句里判断''或者 NULL ，可以设置默认值为0，判断是否=0。
  ```sql
  避免
  SELECT id FROM t WHERE name IS NULL
  可用以下语句替代：
  SELECT id FROM t WHERE uid = 0 
  ```
  `【规则1-2.3】` 避免在 WHERE 子句中使用 OR 来连接条件。
  ```sql
  SELECT id FROM t WHERE uid=10 or uid=20
  替代
  SELECT id FROM t WHERE uid=10
  UNION ALL
  SELECT id FROM t WHERE uid=20
  ```
  
  `【规则1-2.4】` 避免在 WHERE 字句中使用 LIKE %小明%。
  
    注意： `LIKE 小明%` 可利用到索引。
  
```sql
  全表扫描和没有利用索引的情况：
  SELECT id FROM t WHERE name LIKE '%c%'
  或
  SELECT id FROM t WHERE name LIKE '%小明'
  利用到索引的情况：
  SELECT id FROM t WHERE name LIKE '小明%'
```
  
  `【规则1-2.5】`IN 和 NOT IN 慎用,连续值可用BETWEN, 或者用EXISTS代替 如：

```sql
    SELECT id from t WHERE uid in(1,2,3)
    对于连续的数值，尽量用 BETWEEN 替代 IN ：
    SELECT id FROM t WHERE num BETWEEN 1 AND 3
    可用EXISTS代替IN
    SELETC num FROM a WHERE num EXISTS(SELECT 1 FROM b WHERE num=a.num)
```

 `【规则1-2.6】`  SQL语句中避免使用SQL局部变量等,将无法用到查询优化器。
 ```sql
   SELECT id FROM t WHERE name=@NAME
   ```
   
 `【规则1-2.7】` 可强制使用索引。
 ```sql
 SELECT id FROM t WITH(index索引名称) WHERE name ="小明"
 ```
`【1-2.8】` 禁止在SQL语句中 进行表达式操作。
```sql
  SELECT name FROM t WHERE uid/2 = 10
```

`【1-2.9】` 禁止在WHERE 字句的=号 左边进行参数操作。
```sql
SELECT id FROM t WHERE SUBSTRING(name, 1, 3) = ’abc’
```
`【1-2.10】` 使用复合索引时，WHERE 子句的条件，必须用到复合索引的第一个字段，才能利用到该索引。

`【1-2.11】` 一个表的索引，原则上不超过6个，因为会降低INSERT UPDATE等写入语句的效率。

`【1-2.12】` 当一个字段的数据大量重复时，一般情况不需要建立索引，可用ENMU类型代替。

`【1-2.13】` 尽量使用数字型字段，查询时，可将文本字段映射为数字字段，增加查询效率，如uid->username

`【1-2.14】` 使用变长字段代替定长字段，如varchar 替代 var，但是当一个表里面的所有非定长字段全部为定长字段时，查询效率更快。

`【1-2.15】` 任何时候，禁止使用SELECT * FROM t 的*，必须只取需要的字段。

`【1-2.16】` 禁止频繁创建和删除临时表，可采用查询在程序内部处理数据的方法。

`【1-2.17】` 避免查询返回大数据。

`【1-2.18】` 避免大的事务操作，提高性能。

`【1-2.19】` 禁止滥用GROUP BY 比如导出全表数据的查询，或者该字段涉及大量非重复值。

`【1-2.20】` 多看慢查询日志。

`【1-2.21】` JOIN时，小表联大表。

   
   
