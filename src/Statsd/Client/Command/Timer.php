<?php
namespace Statsd\Client\Command;

class Timer extends \Statsd\Client\Command
{
    private $commands = array('timing');

    public function getCommands()
    {
        return $this->commands;
    }

    private function isClosure($var)
    {
        return is_object($var) && ($var instanceof \Closure);
    }

    public function timing($stat, $delta, $rate=1)
    {
        if($this->isClosure($delta)){
            $start_time = gettimeofday(true);
            $delta();
            $end_time = gettimeofday(true);
            $delta = ($end_time - $start_time) * 1000;
        }

        return $this->prepare(
            $stat,
            sprintf('%d|ms', $delta),
            $rate
        );
    }
}
