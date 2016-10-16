<?php
namespace Statsd\Telegraf\Client\Command;

use InvalidArgumentException;

class Timer extends AbstractCommand
{
    private $commands = array('timing', 'timeCallable', 'timingSince');

    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param string          $stat      metric name
     * @param int|callable    $delta     time delta in miliseconds, or callable to call
     * @param float           $rate      sample rate
     * @param array           $tags      associative array of tag name => value
     * @return string|null
     */
    public function timing($stat, $delta, $rate = 1, array $tags = array())
    {
        if ($this->isCallable($delta)) {
            trigger_error(
                'Passing callables to timing() is deprecated. Use timeCallable() instead',
                E_USER_DEPRECATED
            );
            $result = $this->timeCallable($stat, $delta, $rate, $tags);
        } else {
            $result = $this->prepare($stat, sprintf('%d|ms', $delta), $rate, $tags);
        }

        return $result;
    }

    /**
     * @param string          $stat
     * @param callable        $callable
     * @param float           $rate      sample rate
     * @param array           $tags      associative array of tag name => value
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function timeCallable($stat, $callable, $rate = 1, array $tags = array())
    {
        if (!$this->isCallable($callable)) {
            throw new InvalidArgumentException(
                "Can not time none-callable arguments");
        }
        $startTime = gettimeofday(true);
        call_user_func($callable);
        $endTime = gettimeofday(true);
        $delta = ($endTime - $startTime) * 1000;

        return $this->prepare($stat, sprintf('%d|ms', $delta), $rate, $tags);
    }

    /**
     * Send proper timing stats, since the specified starting timestamp.
     * The timing stats will calculate the time passed since the specified
     * timestamp, and send proper metrics.
     *
     * @param string    $stat           the metric name
     * @param int|float $startTime      timestamp of when timing started
     * @param int|float $rate           sampling rate (default = 1)
     * @param array     $tags           associative array of tag name => value
     * @return string|null
     */
    public function timingSince($stat, $startTime, $rate = 1, array $tags = array())
    {
        $delta = (gettimeofday(true) - $startTime) * 1000;
        return $this->prepare($stat, sprintf('%d|ms', $delta), $rate, $tags);
    }

    /**
     * @param  mixed   $callable
     * @return bool
     */
    private function isCallable($callable)
    {
        if (is_array($callable)) {
            if (count($callable) !== 2) {
                return false;
            }
            return method_exists($callable[0], $callable[1]);
        }
        return is_callable($callable);
    }
}
