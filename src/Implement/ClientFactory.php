<?php

namespace Kiwi\AliyunSls\Implement;

use Hyperf\Contract\ConfigInterface;
use Kiwi\AliyunSls\Contact\ClientFactoryInterface;
use Kiwi\AliyunSls\Contact\ClientInterface;
use Psr\Container\ContainerInterface;

class ClientFactory implements ClientFactoryInterface
{
    /**
     * @var ConfigInterface|mixed
     */
    private $config;

    /**
     * @var array
     */
    private $clients;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
    }

    public function get(string $log_group): ClientInterface
    {
        if (isset($this->clients[$log_group])) {
            return $this->clients[$log_group];
        }
        if (!$this->config->has("aliyun_sls.$log_group")) {
            throw new \Exception("$log_group is not configured");
        }
        $client = make(ClientInterface::class)->setConfig($this->config->get("aliyun_sls.$log_group"));
        $this->clients[$log_group] = $client;
        return $client;
    }
}