<?php

declare(strict_types=1);
/**
 * 阿里云日志配置
 */
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
