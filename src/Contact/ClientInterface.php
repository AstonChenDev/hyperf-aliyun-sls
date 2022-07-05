<?php

namespace Aston\AliyunSls\Contact;


use Aston\AliyunSls\Response\GetLogsResponse;
use Aston\AliyunSls\Response\PutLogsResponse;

/**
 * ClientInterface
 * 类的介绍
 * @package Aston\AliyunSls
 */
interface ClientInterface
{
    // Put logs to Log Service.
    public function putLogs(array $contents = [], $topic = '', $shardKey = null): PutLogsResponse;

    public function getLogs(int $from, int $to, $topic = null, $query = '*', $line = 50, $offset = 0, $reverse = true): GetLogsResponse;

    /**
     * Notes: 注入配置
     * User: 陈朋
     * DateTime: 2022/7/5 上午11:48
     * @param array $config
     * @return ClientInterface
     */
    public function setConfig(array $config): ClientInterface;
}