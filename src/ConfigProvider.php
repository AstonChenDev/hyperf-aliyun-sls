<?php

declare(strict_types=1);

namespace Aston\AliyunSls;


use Aston\AliyunSls\Contact\ClientFactoryInterface;
use Aston\AliyunSls\Contact\ClientInterface;
use Aston\AliyunSls\Implement\ClientFactory;

/**
 * ConfigProvider
 * 类的介绍
 * @package Aston\AliyunSls
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ClientInterface::class => Client::class,
                ClientFactoryInterface::class => ClientFactory::class,
            ],
            'processes' => [
            ],
            'listeners' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'collectors' => [
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for aliyun sls.',
                    'source' => __DIR__ . '/../publish/aliyun_sls.php',
                    'destination' => BASE_PATH . '/config/autoload/aliyun_sls.php',
                ],
            ],
        ];
    }
}