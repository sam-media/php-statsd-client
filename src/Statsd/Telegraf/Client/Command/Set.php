<?php
namespace Statsd\Telegraf\Client\Command;


class Set extends AbstractCommand
{
    private $commands = array('set');

    /**
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param string          $stat      metric name
     * @param string          $value     value in the data set
     * @param float           $rate      sample rate
     * @param array           $tags      associative array of tag name => values
     * @return string|null
     */
    public function set($stat, $value, $rate=1, array $tags=array())
    {
        return $this->prepare($stat, sprintf('%s|s', $value), $rate, $tags);
    }
}
