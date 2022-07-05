<?php


namespace Aston\AliyunSls;


use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory as GuzzleClientFactory;
use Aston\AliyunSls\Contact\ClientInterface;
use Aston\AliyunSls\Request\GetLogsRequest;
use Aston\AliyunSls\Request\PutLogsRequest;
use Aston\AliyunSls\Response\GetLogsResponse;
use Aston\AliyunSls\Response\PutLogsResponse;
use Psr\Container\ContainerInterface;

/**
 * Client
 * 类的介绍
 * @package Aston\AliyunSls
 */
class Client implements ClientInterface
{
    /**
     * API版本
     */
    const API_VERSION = '0.6.0';
    /**
     * @var string aliyun accessKeyId
     */
    protected $accessKeyId;

    /**
     * @var string aliyun accessKeySecret
     */
    protected $accessKeySecret;

    /**
     * @var string LOG endpoint
     */
    protected $endpoint;

    /**
     * @var Closure
     */
    private $client;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->client = $container->get(GuzzleClientFactory::class)->create();
    }

    /**
     * Notes: 注入配置
     * User: 陈朋
     * DateTime: 2022/7/5 上午11:45
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): ClientInterface
    {
        $this->config = make(Config::class, [$config]);
        return $this;
    }

    /**
     * GMT format time string.
     *
     * @return string
     */
    protected function getGMT()
    {
        return gmdate('D, d M Y H:i:s') . ' GMT';
    }

    /**
     * parseToJson
     * Decodes a JSON string to a JSON Object.
     * Unsuccessful decode will cause an RuntimeException.
     * @param $resBody
     * @param $requestId
     * @return mixed|null
     */
    protected function parseToJson($resBody, $requestId)
    {
        if (!$resBody) {
            return NULL;
        }
        $result = json_decode($resBody, true);
        if ($result === NULL) {
            throw new \RuntimeException ("Bad format,not json;requestId:{$requestId}");
        }

        return $result;
    }

    /**
     * sendRequest
     * 请求处理响应
     * User：YM
     * Date：2019/12/30
     * Time：下午3:34
     * @param $method
     * @param $url
     * @param $body
     * @param $headers
     * @return array
     */
    public function sendRequest($method, $url, $body, $headers)
    {
        try {
            $response = $this->client->request($method, $url, ['body' => $body, 'headers' => $headers]);
            $responseCode = $response->getStatusCode();
            $header = $response->getHeaders();
            $resBody = (string)$response->getBody();
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }
        $requestId = isset($header['x-log-requestid']) ? $header ['x-log-requestid'] : '';
        if ($responseCode == 200) {
            return [$resBody, $header];
        }
        $exJson = $this->parseToJson($resBody, $requestId);
        if (isset($exJson['error_code']) && isset($exJson['error_message'])) {
            throw new \RuntimeException("{$exJson['error_message']};requestId:{$requestId}", $exJson['error_code']);
        }
        if ($exJson) {
            $exJson = 'The return json is ' . json_encode($exJson);
        } else {
            $exJson = '';
        }
        throw new \RuntimeException("Request is failed. Http code is {$responseCode}.{$exJson};requestId:{$requestId}");
    }

    /**
     * send
     * 组合请求公共数据
     * User：YM
     * Date：2019/12/30
     * Time：下午3:35
     * @param $method
     * @param $project
     * @param $body
     * @param $resource
     * @param $params
     * @param $headers
     * @return array
     */
    public function send($method, $project, $body, $resource, $params, $headers)
    {
        $accessKey = $this->config->get('access_key', '');
        $secretKey = $this->config->get('secret_key', '');
        $endpoint = $this->config->get('endpoint', '');
        if ($body) {
            $headers['Content-Length'] = strlen($body);
            $headers["x-log-bodyrawsize"] = $headers["x-log-bodyrawsize"] ?? 0;
            $headers['Content-MD5'] = LogUtil::calMD5($body);
        } else {
            $headers['Content-Length'] = 0;
            $headers["x-log-bodyrawsize"] = 0;
            $headers['Content-Type'] = '';
        }
        $headers['x-log-apiversion'] = self::API_VERSION;
        $headers['x-log-signaturemethod'] = 'hmac-sha1';
        $host = is_null($project) ? $endpoint : "{$project}.{$endpoint}";
        $headers['Host'] = $host;
        $headers['Date'] = $this->getGMT();
        $signature = LogUtil::getSignature($method, $resource, $secretKey, $params, $headers);
        $headers['Authorization'] = "LOG $accessKey:$signature";
        $url = "http://{$host}{$resource}";
        if ($params) {
            $url .= '?' . LogUtil::urlEncode($params);
        }

        return $this->sendRequest($method, $url, $body, $headers);
    }

    /**
     * putLogs
     * Put logs to Log Service
     * Unsuccessful opertaion will cause an RuntimeException
     * User：YM
     * Date：2019/12/30
     * Time：下午4:51
     * @param array $contents
     * @param string $topic
     * @param null $project
     * @param null $logstore
     * @param null $shardKey
     * @return mixed
     */
    public function putLogs(array $contents = [], $topic = '', $shardKey = null): PutLogsResponse
    {
        $project = $this->config->get('project', '');
        $logstore = $this->config->get('logstore', '');
        $source = LogUtil::getLocalIp();
        $logItems = array(make(LogItem::class, [time(), $contents]));
        $request = make(PutLogsRequest::class, [$project, $logstore, $topic, $source, $logItems, $shardKey]);

        if (count($request->getLogItems()) > 4096) {
            throw new \RuntimeException('PutLogs 接口每次可以写入的日志组数据量上限为4096条!');
        }
        $logGroup = make(LogGroup::class);
        $logGroup->setTopic($request->getTopic());
        $logGroup->setSource($request->getSource());
        foreach ($request->getLogItems() as $logItem) {
            $log = make(Log::class);
            $log->setTime($logItem->getTime());
            $contents = $logItem->getContents();
            foreach ($contents as $key => $value) {
                $content = make(LogContent::class);
                $content->setKey($key);
                $content->setValue($value);
                $log->addContents($content);
            }
            $logGroup->addLogs($log);
        }
        $body = LogUtil::toBytes($logGroup);
        unset($logGroup);
        $bodySize = strlen($body);
        if ($bodySize > 3 * 1024 * 1024) {
            throw new \RuntimeException('PutLogs 接口每次可以写入的日志组数据量上限为3MB!');
        }
        $params = [];
        $headers = [];
        $headers["x-log-bodyrawsize"] = $bodySize;
        $headers['x-log-compresstype'] = 'deflate';
        $headers['Content-Type'] = 'application/x-protobuf';
        if ($shardKey) {
            $headers["x-log-hashkey"] = $shardKey;
        }
        $body = gzcompress($body, 6);
        $resource = "/logstores/" . $request->getLogstore() . "/shards/lb";
        list($resp, $header) = $this->send("POST", $project, $body, $resource, $params, $headers);
        $requestId = isset($header['x-log-requestid']) ? $header['x-log-requestid'] : '';
        $resp = $this->parseToJson($resp, $requestId);
        return make(PutLogsResponse::class, [$header]);
    }


    /**
     * GetLogsRequest $request the GetLogs request parameters class.
     * User： 陈朋
     * Date：2021/6/4
     * Time：下午2:08
     */
    public function getLogsJson(GetLogsRequest $request)
    {
        $headers = array();
        $params = array();
        if ($request->getTopic() !== null)
            $params ['topic'] = $request->getTopic();
        if ($request->getFrom() !== null)
            $params ['from'] = $request->getFrom();
        if ($request->getTo() !== null)
            $params ['to'] = $request->getTo();
        if ($request->getQuery() !== null)
            $params ['query'] = $request->getQuery();
        $params ['type'] = 'log';
        if ($request->getLine() !== null)
            $params ['line'] = $request->getLine();
        if ($request->getOffset() !== null)
            $params ['offset'] = $request->getOffset();
        if ($request->getOffset() !== null)
            $params ['reverse'] = $request->getReverse() ? 'true' : 'false';
        $logstore = $request->getLogstore() !== null ? $request->getLogstore() : '';
        $project = $request->getProject() !== null ? $request->getProject() : '';
        $resource = "/logstores/$logstore";
        list ($resp, $header) = $this->send("GET", $project, NULL, $resource, $params, $headers);
        $requestId = isset ($header ['x-log-requestid']) ? $header ['x-log-requestid'] : '';
        $resp = $this->parseToJson($resp, $requestId);
        return array($resp, $header);
        //return new Aliyun_Log_Models_GetLogsResponse ( $resp, $header );
    }


    /**
     * Get logs from Log service.
     * Unsuccessful opertaion will cause an RuntimeException
     * User： 陈朋
     * Date：2021/6/4
     * Time：下午2:08
     * @param $project
     * @param $logstore
     * @param $from
     * @param $to
     * @param $topic
     * @param $query
     * @param $line
     * @param $offset
     * @param $reverse
     * @return GetLogsResponse
     */
    public function getLogs(int $from, int $to, $topic = null, $query = '*', $line = 50, $offset = 0, $reverse = true): GetLogsResponse
    {
        $to = $to ?: time();
        $from = $from ?: strtotime(date("Y-m-d"), time());
        $project = $this->config->get('project', '');
        $logstore = $this->config->get('logstore', '');
        $request = make(GetLogsRequest::class, [$project, $logstore, $from, $to, $topic, $query, $line, $offset, $reverse]);
        $ret = $this->getLogsJson($request);
        $resp = $ret[0];
        $header = $ret[1];
        return make(GetLogsResponse::class, [$resp, $header]);
    }
}