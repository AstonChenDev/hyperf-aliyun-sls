<?php

namespace Aston\AliyunSls;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Context\Context;
use Aston\AliyunSls\Contact\ClientFactoryInterface;
use Aston\AliyunSls\Contact\ClientInterface;

class Logger
{
    /**
     * @var bool
     */
    private $write_switch;


    /**
     * @var ClientInterface
     */
    private $client;

    private function __construct(string $log_group)
    {
        $this->write_switch = (bool)ApplicationContext::getContainer()->get(ConfigInterface::class)->get('aliyun_sls.write_switch', false);
        $this->client = ApplicationContext::getContainer()->get(ClientFactoryInterface::class)->get($log_group);
    }

    /**
     * Notes: 写日志
     * User: 陈朋
     * DateTime: 2022/7/5 下午2:27
     * @param string $topic
     * @param array $contents
     * @param $project
     * @param $logstore
     * @param $shardKey
     * @return void
     */
    public function write(string $topic = '', array $contents = [], $project = null, $logstore = null, $shardKey = null)
    {
        //写入日志总开关
        if (!$this->write_switch) {
            return;
        }
        go(function () use ($topic, $contents, $project, $logstore, $shardKey) {
            $contents['log_time'] = $this->getMillisecond();
            $result = [];
            foreach ($contents as $key => $item) {
                if ($key == 'response' && isset($item['data'])) {
                    $response_data = is_array($item['data']) ? $item['data'] : [$item['data']];
                    unset($item['data']);
                    $item = array_merge($item, $response_data);
                }
                $result[$key] = is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $item;
            }
            try {
                $this->client->putLogs($result, $topic);
            } catch (\Throwable $e) {
                var_dump($e->getFile());
                var_dump($e->getLine());
                var_dump($e->getMessage());
            }
        });
    }

    /**
     * Notes: 读取日志
     * User: 陈朋
     * DateTime: 2022/7/5 下午5:28
     * @param int $from
     * @param int $to
     * @param string $topic
     * @param string $query
     * @param int $line
     * @param int $offset
     * @param bool $reverse
     * @return array
     */
    public function read(int $from, int $to, string $topic = '', string $query = '*', int $line = 50, int $offset = 0, bool $reverse = true): array
    {
        $args = func_get_args();
        if ($topic) {
            $query = "__topic__: $topic and $query";
            $topic = '';
            $args[2] = $topic;
            $args[3] = $query;
        }
        try {
            $response = $this->client->getLogs(...$args);
            $logs = [];
            foreach ($response->getLogs() as $log) {
                $logs[] = $log->getContents();
            }
        } catch (\Throwable $throwable) {
            var_dump($throwable->getMessage());
            return [];
        }
        return $logs;
    }

    /**
     * Notes: 获取当前毫秒
     * User: 陈朋
     * DateTime: 2021/7/8 11:33
     * @return int
     */
    public static function getMillisecond(): int
    {
        [$t1, $t2] = explode(' ', microtime());
        return (int)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }


    /**
     * @param string $log_group
     * @param bool $refresh
     * @return static
     */
    public static function instance(string $log_group = 'default', bool $refresh = false): ?Logger
    {
        $key = get_called_class() . $log_group;
        $instance = null;
        if (Context::has($key)) {
            $instance = Context::get($key);
        }

        if ($refresh || !$instance instanceof static) {
            $instance = new static($log_group);
            Context::set($key, $instance);
        }

        return $instance;
    }
}