<?php

//declare(strict_types=1);
/**
 * Created by PhpStorm.
 *​
 * LogUtil.php
 *
 * 日志工具
 *
 * User：YM
 * Date：2019/12/24
 * Time：下午12:35
 */


namespace Kiwi\AliyunSls;


/**
 * LogUtil
 * 日志工具
 * @package Kiwi\AliyunSls
 * User：YM
 * Date：2019/12/24
 * Time：下午12:35
 */
class LogUtil
{
    /**
     * getLocalIp
     * 获取本地ip
     * User：YM
     * Date：2019/12/24
     * Time：下午12:37
     * @static
     * @return string
     */
    public static function getLocalIp()
    {
        $ip = '127.0.0.1';
        $ips = array_values(swoole_get_local_ip());
        foreach ($ips as $v) {
            if ($v && $v != $ip) {
                $ip = $v;
                break;
            }
        }

        return $ip;
    }

    /**
     * calMD5
     * 计算大写的md5
     * User：YM
     * Date：2019/12/24
     * Time：下午12:43
     * @static
     * @param $value
     * @return string
     */
    public static function calMD5($value)
    {
        return strtoupper(md5($value));
    }

    /**
     * hmacSHA1
     * Calculate string $content hmacSHA1 with secret key $key.
     * User：YM
     * Date：2019/12/24
     * Time：下午12:45
     * @static
     * @param $content
     * @param $key
     * @return string
     */
    public static function hmacSHA1($content, $key)
    {
        $signature = hash_hmac("sha1", $content, $key, true);
        return base64_encode($signature);
    }

    /**
     * urlEncode
     * Get url encode.
     * User：YM
     * Date：2019/12/24
     * Time：下午12:47
     * @static
     * @param $params
     * @return string
     */
    public static function urlEncode($params)
    {
        ksort ( $params );
        $url = "";
        $first = true;
        foreach ( $params as $key => $value ) {
            $val = urlencode($value);
            if ($first) {
                $first = false;
                $url = "$key=$val";
            } else
                $url .= "&$key=$val";
        }
        return $url;
    }

    /**
     * handleLOGHeaders
     * 处理请求头，规范一下
     * User：YM
     * Date：2019/12/24
     * Time：下午12:55
     * @static
     * @param $header
     * @return string
     */
    public static function handleLOGHeaders($header)
    {
        ksort ( $header );
        $content = '';
        $first = true;
        foreach ( $header as $key => $value )
            if (strpos ( $key, "x-log-" ) === 0 || strpos ( $key, "x-acs-" ) === 0) {
                if ($first) {
                    $content .= $key . ':' . $value;
                    $first = false;
                } else
                    $content .= "\n" . $key . ':' . $value;
            }
        return $content;
    }

    /**
     * handleResource
     * 规范resource
     * User：YM
     * Date：2019/12/24
     * Time：下午12:56
     * @static
     * @param $resource
     * @param $params
     * @return string
     */
    public static function handleResource($resource, $params)
    {
        if ($params) {
            ksort ( $params );
            $urlString = "";
            $first = true;
            foreach ( $params as $key => $value ) {
                if ($first) {
                    $first = false;
                    $urlString = "$key=$value";
                } else
                    $urlString .= "&$key=$value";
            }
            return $resource . '?' . $urlString;
        }
        return $resource;
    }

    /**
     * getSignature
     * 获取签名
     * User：YM
     * Date：2019/12/24
     * Time：下午1:05
     * @static
     * @param $method
     * @param $resource
     * @param $accessKeySecret
     * @param $params
     * @param $headers
     * @return string
     */
    public static function getSignature($method, $resource, $accessKeySecret, $params, $headers)
    {
        if ( !$accessKeySecret ) {
            return '';
        }
        $content = $method . "\n";
        if ( isset($headers['Content-MD5']) ) {
            $content .= $headers['Content-MD5'];
        }
        $content .= "\n";
        if ( isset($headers['Content-Type']) ) {
            $content .= $headers['Content-Type'];
        }
        $content .= "\n";
        $content .= $headers['Date'] . "\n";
        $content .= self::handleLOGHeaders($headers) . "\n";
        $content .= self::handleResource ($resource, $params);
        return self::hmacSHA1($content, $accessKeySecret);
    }

    /**
     * toBytes
     * Change $logGroup to bytes.
     * User：YM
     * Date：2019/12/24
     * Time：下午7:38
     * @static
     * @param $logGroup
     * @return bool|string
     */
    public static function toBytes($logGroup) {
        $mem = fopen("php://memory", "rwb");
        $logGroup->write($mem);
        rewind($mem);
        $bytes="";

        if(feof($mem)===false){
            $bytes = fread($mem, 10*1024*1024);
        }
        fclose($mem);
        return $bytes;
    }
}