<?php

//declare(strict_types=1);
/**
 * Created by PhpStorm.
 *​
 * PutLogsResponse.php
 *
 * 文件描述
 *
 * User：YM
 * Date：2019/12/30
 * Time：下午4:46
 */


namespace Kiwi\AliyunSls\Response;


/**
 * PutLogsResponse
 *The response of the PutLogs API from log service.
 * @author log service dev
 * @package Kiwi\AliyunSls\Response
 * User：YM
 * Date：2019/12/30
 * Time：下午4:46
 */
class PutLogsResponse extends Response
{
    /**
     * PutLogsResponse constructor.
     * @param $headers PutLogs HTTP response header
     */
    public function __construct($headers)
    {
        parent::__construct($headers);
    }
}