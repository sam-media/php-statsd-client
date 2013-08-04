<?php
namespace Statsd\Client\Command;

class Counter extends \Statsd\Client\Command
{
    private $commands = array('incr', 'decr');

    public function getCommands()
    {
        return $this->commands;
    }

    public function incr($stat, $count=1, $rate=1)
    {
        return $this->prepare(
            $stat,
            sprintf('%s|c', $count),
            $rate
        );
    }

    public function decr($stat, $count=1, $rate=1)
    {
        return $this->incr($stat, -1*$count, $rate);
    }
}
