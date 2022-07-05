# hyperf-aliyun-sls

hyperf框架使用的阿里云日志写入与读取

可指定通过多个sls配置读取与写入

require：

"hyperf/guzzle": "^2.0.0",
"hyperf/contract": "^2.0.0",
"psr/container": "^1.0|^2.0",
"php": ">=7.2",
"ext-zlib": "*",
"ext-json": "*"

## 安装

使用 composer

```
composer require aston/aliyun-sls
```

发布配置文件

```
php bin/hyperf.php vendor:publish aston/aliyun-sls
```

## 配置文件说明

```php
return [
    'write_switch' => (bool)env('WRITE_SLS', true),//写日志开关
    //默认日志配置组
    'default' => [
        'endpoint' => env('ALIYUN_SLS_ENDPOINT'),
        'access_key' => env('ALIYUN_SLS_AK'),
        'secret_key' => env('ALIYUN_SLS_SK'),
        'project' => env('ALIYUN_SLS_PROJECT'),
        'logstore' => env('ALIYUN_SLS_LOGSTORE'),
    ],
    //自定义日志配置组 可选
    'another' => [
        'endpoint' => env('ANOTHER_ALIYUN_SLS_ENDPOINT'),
        'access_key' => env('ANOTHER_ALIYUN_SLS_AK'),
        'secret_key' => env('ANOTHER_ALIYUN_SLS_SK'),
        'project' => env('ANOTHER_ALIYUN_SLS_PROJECT'),
        'logstore' => env('ANOTHER_ALIYUN_SLS_LOGSTORE'),
    ]
];
```

## 使用方法演示

```php

use Aston\AliyunSls\Logger;

//通过默认日志配置 写入
Logger::instance()->write('testLog', [
    'is_test' => 1
]);


//通过指定日志配置 读取
$log = Logger::instance('appsflyer')->read(
   $start_timestamp,
   $end_timestamp,
   $topic,
   '* |SELECT "message.customer_user_id" limit 1'
);

```
