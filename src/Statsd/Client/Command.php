<?php
namespace Statsd\Client;

abstract class Command implements CommandInterface
{
    protected function prepare($stat, $value, $rate=1)
    {
        if ($rate < 1 ) {
            if ($this->genRand() < $rate) {
                $value = sprintf('%s|@%s', $value, $rate);
            } else {
                return;
            }
        }
        return sprintf('%s:%s', $stat, $value);
    }

    public function genRand()
    {
        return (float) mt_rand() / (float) mt_getrandmax();
    }

    /**
     * @param  mixed   $callable
     * @return bool
     */
    protected function isCallable($callable)
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
