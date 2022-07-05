<?php

namespace Kiwi\AliyunSls\Contact;

interface ClientFactoryInterface
{
    public function get(string $log_group): ClientInterface;
}