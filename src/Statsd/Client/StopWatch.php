<?php
namespace Statsd\Client;

use Statsd\AbstractStopWatch;
use Statsd\Client;

class StopWatch extends AbstractStopWatch
{
    /**@var \Statsd\Client */
    protected $client = null;

    /**
     * StopWatch object to easily send timing stats metrics
     *
     * @param \Statsd\Client $client
     * @param float reference timestamp
     */
    public function __construct(Client $client, $reference = null)
    {
        $this->client = $client;
        $this->reference = $reference === null ? microtime(true) : (float) $reference;
    }

    /**
     * Returns the statsd client used to send metrics
     *
     * @return \Statsd\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Send metrics with duration passed since the reference time
     *
     * @param string $metric
     * @param int|float $rate sample rate
     * @return \Statsd\Client\StopWatch self reference
     */
    public function send($metric, $rate = 1)
    {
        $this->client->timing($metric, $this->elapsedMilliseconds(), $rate);
        return $this;
    }
}
