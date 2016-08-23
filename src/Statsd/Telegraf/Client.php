<?php
namespace Statsd\Telegraf;

use Statsd\Telegraf\Client\Command\Counter;
use Statsd\Telegraf\Client\Command\Set;
use Statsd\Telegraf\Client\Command\Timer;
use Statsd\Telegraf\Client\Command\Gauge;


class Client extends \Statsd\Client
{
    /**
     * Returns associative array of deafult settings.
     *
     * @return array
     */
    protected static function getDefaultSettings()
    {
        return array(
                    'prefix' => '',
                    'throw_exception' => false,
                    'connection' => null,
                    'tags' => array()
                );
    }
}
