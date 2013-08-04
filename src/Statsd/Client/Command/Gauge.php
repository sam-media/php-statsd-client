<?php
namespace Statsd\Client\Command;

class Gauge extends \Statsd\Client\Command
{
    private $commands = array('gauge');

    public function getCommands()
    {
        return $this->commands;
    }

    public function gauge($stat, $value, $rate=1, $delta=false)
    {
        if(is_bool($rate) && $rate === true) {
            $delta = true;
        }

        if ($delta) {
            $value = sprintf('%+g|g', $value);
        } else {
            $value = sprintf('%g|g', $value);
        }
        return $this->prepare(
            $stat,
            $value,
            $rate
        );
    }
}
