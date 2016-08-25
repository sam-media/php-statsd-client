<?php
namespace Statsd\Telegraf\Client\Command;


class Timer extends AbstractCommand
{
    private $commands = array('timing');

    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param string          $stat      metric name
     * @param int|callable    $delta     time delta in miliseconds, or callable to call
     * @param float           $rate      sample rate
     * @param array           $tags      associative array of tag name => values
     * @return string|null
     */
    public function timing($stat, $delta, $rate=1, array $tags=array())
    {
        if (is_callable($delta)) {
            $startTime = gettimeofday(true);
            $delta();
            $endTime = gettimeofday(true);
            $delta = ($endTime - $startTime) * 1000;
        }

        return $this->prepare($stat, sprintf('%d|ms', $delta), $rate, $tags);
    }
}
