<?php
namespace Statsd\Telegraf\Client\Command;


class Counter extends AbstractCommand
{
    private $commands = array('incr', 'decr');

    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param string $stat      metric name
     * @param int    $count     counter
     * @param float  $rate      sample rate
     * @param array  $tags      associative array of tag name => values
     * @return string|null
     */
    public function incr($stat, $count=1, $rate=1, array $tags=array())
    {
        return $this->prepare($stat, sprintf('%s|c', $count), $rate, $tags);
    }

    /**
     * @param string $stat      metric name
     * @param int    $count     counter
     * @param float  $rate      sample rate
     * @param array  $tags      associative array of tag name => values
     * @return string|null
     */
    public function decr($stat, $count=1, $rate=1, array $tags=array())
    {
        return $this->incr($stat, -1 * $count, $rate, $tags);
    }
}
