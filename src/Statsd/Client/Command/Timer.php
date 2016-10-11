<?php
namespace Statsd\Client\Command;

use InvalidArgumentException;

class Timer extends \Statsd\Client\Command
{
    private $commands = array('timing', 'timingSince', 'timeCallable');

    public function getCommands()
    {
        return $this->commands;
    }

    private function isClosure($var)
    {
        return is_object($var) && ($var instanceof \Closure);
    }

    /**
     * Send timing stats, provide the timing metric in milliseconds.
     *
     * Note: previous versions support accepting a callable instead
     * of the duration, so the callable is measrued and timing is sent.
     * This behavior is deprecated (will be removed in future versions),
     * use timeCallable() instead.
     *
     * @param string $stat the metric name
     * @param int $delta duration in milliseconds
     * @param int $rate sampling rate (default = 1)
     * @return string
     */
    public function timing($stat, $delta, $rate=1)
    {
        if ($this->isClosure($delta)) {
            $startTime = gettimeofday(true);
            $delta();
            $endTime = gettimeofday(true);
            $delta = ($endTime - $startTime) * 1000;
        }

        return $this->prepare(
            $stat,
            sprintf('%d|ms', $delta),
            $rate
        );
    }

    /**
     * Send proper timing stats, since the specified starting timestamp.
     * The timing stats will calculated the time passed since the specified
     * timestamp, and send proper metrics.
     *
     * @param string $stat the metric name
     * @param int|float $startTime timestamp of when timing started
     * @param int|float $rate sampling rate (default = 1)
     * @return string
     */
    public function timingSince($stat, $startTime, $rate=1)
    {
        $delta = (gettimeofday(true) - $startTime) * 1000;

        return $this->prepare(
            $stat,
            sprintf('%d|ms', $delta),
            $rate
        );
    }

    /**
     * Run the callable param send the timing metrics for the duration.
     *
     * @param string $stat
     * @param callable $callable
     * @param int|float $rate
     * @throws \InvalidArgumentException
     * @return string
     */
    public function timeCallable($stat, $callable, $rate=1)
    {
        if (!$this->isCallable($callable)) {
            throw new InvalidArgumentException(
                "Can not time none-callable arguments");
        }

        $startTime = gettimeofday(true);
        $callable();
        $endTime = gettimeofday(true);
        $delta = ($endTime - $startTime) * 1000;

        return $this->timing($stat, $delta, $rate);
    }
}
