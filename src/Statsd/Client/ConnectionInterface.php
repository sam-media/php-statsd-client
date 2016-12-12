<?php
namespace Statsd\Client;

interface ConnectionInterface
{
    public function __construct(array $settings);
    public function send($msg);
}
