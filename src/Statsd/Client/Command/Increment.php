<?php
namespace Statsd\Client\Command;

class Increment extends \Statsd\Client\Command
{
    private $commands = array('incr');

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
}
