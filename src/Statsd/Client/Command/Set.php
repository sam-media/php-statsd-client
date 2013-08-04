<?php
namespace Statsd\Client\Command;

class Set extends \Statsd\Client\Command
{
    private $commands = array('set');

    public function getCommands()
    {
        return $this->commands;
    }

    public function set($stat, $value, $rate=1)
    {
        return $this->prepare(
            $stat,
            sprintf('%s|s', $value),
            $rate
        );
    }
}
