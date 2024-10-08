# php-sync
php 互斥锁

| 锁类型                               | 堵塞类型 | 分布式 | 锁时间  | 多个锁加锁解锁| 客户端异常终止(加锁解锁) |
| :---------------------------------- | :------ | :----  | :---- | :-------------- | :------------------- |
| 互斥锁(Mutex)                        | 堵塞     | 支持  | 毫秒级  | 支持            | 支持(未实现)           |
| 业务互斥锁(BusinessMutex)             | 堵塞    | 支持   | 毫秒级 | 支持             | 支持(未实现)           |
| 示例订单业务互斥锁(OrderBusinessMutex) | 非堵塞   | 支持   | 毫秒级 | 支持            | 支持(未实现)            |

***仓储类型请使用非堵塞类型***

| 锁仓储类型 | 是否支持 |堵塞类型 | 多个锁加锁解锁| 客户端异常终止(加锁解锁) |
| :------- | :----  |:----  |:----  | :----------------------------- |
| Redis    | 支持   | 非堵塞  | 支持 | 支持               |
| Mysql    | 支持  | 非堵塞  | 支持 | 支持               |

```
优势: 
  1. 提升用户体验
  2. 减少修改代码影响范围
  3. 减少程序修复时间

场景: 
 1. 业务禁止多人同时操作
 2. 业务有单个和批量操作
 2. 订单出库
 3. 新增入库单
```

## 安装方式1
> 创建composer.json文件,并写入以下内容:

```php
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/qq1060656096/php-sync.git"
        }
    ],
    "require": {
        "zwei/php-sync": "dev-master"
    }
}
```
> 执行composer install

### 使用Mysql仓储请导入sql到数据库中
```
CREATE TABLE `sync_lock` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '锁名',
  `expired` decimal(30,0) NOT NULL COMMENT '有效期多少毫秒',
  `expired_time` decimal(30,0) NOT NULL DEFAULT '0' COMMENT '过期时间',
  `client_id` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'client_id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1;
```



### 示例：订单业务锁示例1
```php
<?php
use Zwei\Sync\Exception\LockFailException;
use Zwei\Sync\Exception\LockTimeoutException;
use Zwei\Sync\Exception\UnLockTimeoutException;
use Zwei\Sync\Helper\Helper;
use Zwei\Sync\Mutex\OrderBusinessMutex;
use Zwei\Sync\Repository\RedisLockRepository;
try {
    $host = '199.199.199.199';
    $post = 16379;
    $password = '000000';
    $redis = new \Redis();
    $redis->connect($host, $post);
    $redis->auth($password);
    
    $redisRepository = new RedisLockRepository($redis);
    $expired = Helper::secondsToMilliseconds(30);
    $orderId = 1;
    $orderDemoBusinessMutex = new OrderBusinessMutex($redisRepository, $expired, $orderId);
    $orderDemoBusinessMutex->lock();
    // todo
    $orderDemoBusinessMutex->unlock();
} catch (LockParamException $exception) {
    // 参数错误
}  catch (LockFailException $exception) {
    // 其他人正在操作, 请稍后在试
} catch (LockTimeoutException $exception) {
    // 加锁超时
} catch (UnLockTimeoutException $exception) {
    // 解锁超时
} catch (NoLockUnLockFailException $exception) {
    // 没有加锁时，解锁
}
```

### 示例：订单业务锁示例2
```php
<?php
use Zwei\Sync\Exception\LockFailException;
use Zwei\Sync\Exception\LockTimeoutException;
use Zwei\Sync\Exception\UnLockTimeoutException;
use Zwei\Sync\Helper\Helper;
use Zwei\Sync\Mutex\OrderBusinessMutex;
use Zwei\Sync\Repository\RedisLockRepository;
try {
    $host = '199.199.199.199';
    $post = 16379;
    $password = '000000';
    $redis = new \Redis();
    $redis->connect($host, $post);
    $redis->auth($password);

    $redisRepository = new RedisLockRepository($redis);
    $expired = Helper::secondsToMilliseconds(30);
    $orderId = 1;
    $orderBusinessMutex = new OrderBusinessMutex($redisRepository, $expired, $orderId);
    $orderBusinessMutex->synchronized(function(){
        // todo
    });
} catch (LockParamException $exception) {
    // 参数错误
}  catch (LockFailException $exception) {
    // 其他人正在操作, 请稍后在试
} catch (LockTimeoutException $exception) {
    // 加锁超时
} catch (UnLockTimeoutException $exception) {
    // 解锁超时
} catch (NoLockUnLockFailException $exception) {
    // 没有加锁时，解锁
}
```


### 单元测试使用
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests

```
redis-cli -h 199.199.199.199 -p 16379 -a 000000

# 测试业务互斥锁(非堵塞):20秒内多次加锁会失败
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/BusinessMutexTest.php --filter=testLock

# 测试业务互斥锁(非堵塞):20秒内多次加锁会异常
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/BusinessMutexTest.php --filter=testLockFail

# 测试业务互斥锁(非堵塞):加锁解锁
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/BusinessMutexTest.php --filter=testLockAndUnlock

# 测试业务互斥锁(非堵塞):闭包调用
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/BusinessMutexTest.php --filter=testSynchronized

# 测试互斥锁(非堵塞):解锁超时
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/BusinessMutexTest.php --filter=testSynchronizedUnlockTimeOut
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/BusinessMutexTest.php --filter=testUnlockTimeout

# 测试互斥锁(非堵塞):未加时,解锁异常
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/BusinessMutexTest.php --filter=testUnlockNoLockUnLockFailException

# 测试互斥锁(堵塞):20秒内多次加锁会堵塞
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/MutexTest.php --filter=testLock

# 测试互斥锁(堵塞):加锁解锁
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/MutexTest.php --filter=testLockAndUnlock

# 测试互斥锁(堵塞):闭包调用
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/MutexTest.php --filter=testSynchronized


# 测试互斥锁(堵塞):解锁超时
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/MutexTest.php --filter=testSynchronizedUnlockTimeOut
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/MutexTest.php --filter=testUnlockTimeout

```

### 订单业务锁
```
# 测试互斥锁(堵塞):解锁超时
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/OrderBusinessMutexTest.php --filter=testSynchronizedUnlockTimeOut
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/OrderBusinessMutexTest.php --filter=testUnlockTimeout


# mysql仓储互斥锁
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Mutex/MysqlBusinessMutexTest.php

```

### redis lua脚本

```sh
# 加锁lua
redis-cli -h 199.199.199.199 -p 16379 -a 000000 --eval src/lua/redis.lock.lua key1 key2 , clientId expired

# 解锁lua
redis-cli -h 199.199.199.199 -p 16379 -a 000000 --eval src/lua/redis.unlock.lua key1 key2 , clientId
```

### redis rpop 原子操作
```sh
php vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Atomic/RedisAtomicTest.php

/usr/bin/php7.3 vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/Atomic/RedisAtomicTest.php --filter=testExampleNoData
```

```php
<?php
use Zwei\Sync\Atomic\RedisAtomic;
$queueName = "q1";
$queueAckName = "q1_ack";

// redis ack 队列消费
$redis = new \Redis();
$result = $redis->connect('172.29.112.1', 6379);
$redisAtomic = new RedisAtomic($redis);
// 没有数据会自动退出
$redisAtomic->rPopAutoAck(1000, $queueName, $queueAckName, function ($data) {
    sleep(1);
});

// 如果想一直消费直接 while(true)
while (true) {
    $redisAtomic->rPopAutoAck(1000, $queueName, $queueAckName, function ($data) {
        sleep(1);
    });
}
```


```shell
# 推送到队列
del q1 q1c
lpush q1 v1
lpush q1 v2
lpush q1 v3
lpush q1 v4
```