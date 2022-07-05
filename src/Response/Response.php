<?php

//declare(strict_types=1);
/**
 * Created by PhpStorm.
 *​
 * Response.php
 *
 * User：YM
 * Date：2019/12/30
 * Time：下午4:44
 */


namespace Kiwi\AliyunSls\Response;


/**
 * Response
 * aliyun sdk response
 * The base response class of all log response.
 * @author log service dev
 * @package Kiwi\AliyunSls\Response
 * User：YM
 * Date：2019/12/30
 * Time：下午4:44
 */
class Response
{
    /**
     * @var array HTTP response header
     */
    private $headers;

    /**
     * Aliyun_Log_Models_Response constructor
     *
     * @param array $header
     *            HTTP response header
     */
    public function __construct($headers) {
        $this->headers = $headers;
    }

    /**
     * Get all http headers
     *
     * @return array HTTP response header
     */
    public function getAllHeaders() {
        return $this->headers;
    }

    /**
     * Get specified http header
     *
     * @param string $key
     *            key to get header
     *
     * @return string HTTP response header. '' will be return if not set.
     */
    public function getHeader($key) {
        return isset ($this->headers[$key]) ? $this->headers [$key] : '';
    }

    /**
     * Get the request id of the response. '' will be return if not set.
     *
     * @return string request id
     */
    public function getRequestId() {
        return isset ( $this->headers ['x-log-requestid'] ) ? $this->headers ['x-log-requestid'] : '';
    }
}