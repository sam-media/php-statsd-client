<?php
namespace Statsd\Client;

use Statsd\Client;

class StopWatch
{
    /**@var \Statsd\Client */
    protected $client = null;
    /**@var float */
    protected $reference = 0;

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
     * Returns reference timestamp
     *
     * @return float
     */
    public function getReferenceTimestamp()
    {
        return $this->reference;
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
        $this->client->timing($metric, $this->elapsed(), $rate);
        return $this;
    }

    /**
     * Returns milliseconds since reference time
     * @return int
     */
    private function elapsed()
    {
        return intval((microtime(true) - $this->reference) * 1000);
    }
}
