<?php
namespace Statsd;

abstract class AbstractStopWatch
{
    /**@var float */
    protected $reference = 0;

    /**
     * Returns reference timestamp (seconds)
     *
     * @return float
     */
    public function getReferenceTimestamp()
    {
        return $this->reference;
    }

    /**
     * Returns milliseconds since reference time
     *
     * @return int
     */
    protected function elapsedMilliseconds()
    {
        return intval((microtime(true) - $this->reference) * 1000);
    }
}
