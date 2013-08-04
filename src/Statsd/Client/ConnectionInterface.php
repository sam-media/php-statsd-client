<?php
namespace Statsd\Client;

interface ConnectionInterface
{
    public function __construct($settings);
    public function send($msg);
}
