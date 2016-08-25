<?php
namespace Statsd\Telegraf\Client\Command;


class Gauge extends AbstractCommand
{
    private $commands = array('gauge');

    /**
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param string          $stat      metric name
     * @param float|int       $value     gauge value
     * @param float           $rate      sample rate
     * @param bool            $delta     if the value is a delta
     * @param array           $tags      associative array of tag name => values
     * @return string|null
     */
    public function gauge($stat, $value, $rate=1, $delta=false, array $tags=array())
    {
        /**
         * I prefer not to mix parameters, but really want to be API compatible
         * with Statsd\Client\Command classes
         */
        if (is_bool($rate)) {
            $delta = $rate;
            $rate = 1;
        }

        $statValue = $delta ? sprintf('%+g|g', $value) : sprintf('%g|g', $value);
        return $this->prepare($stat, $statValue, $rate, $tags);
    }
}
