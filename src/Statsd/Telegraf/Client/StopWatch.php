<?php
namespace Statsd\Telegraf\Client;

use Statsd\AbstractStopWatch;
use Statsd\Telegraf\Client;

class StopWatch extends AbstractStopWatch
{
    /**@var \Statsd\Telegraf\Client */
    protected $client = null;

    /**
     * StopWatch object to easily send timing stats metrics
     *
     * @param \Statsd\Client $client
     * @param float reference timestamp (seconds)
     */
    public function __construct(Client $client, $reference = null)
    {
        $this->client = $client;
        $this->reference = $reference === null ? microtime(true) : (float) $reference;
    }

    /**
     * Returns the statsd client used to send metrics
     *
     * @return \Statsd\Telegraf\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Send metrics with duration passed since the reference time
     *
     * @param string    $metric metric name
     * @param int|float $rate   sample rate
     * @param array     $tags   associative array of tags
     * @return \Statsd\Telegraf\Client\StopWatch        self reference
     */
    public function send($metric, $rate = 1, array $tags = array())
    {
        $this->client->timing($metric, $this->elapsedMilliseconds(), $rate, $tags);
        return $this;
    }
}
