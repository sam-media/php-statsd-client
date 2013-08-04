<?php
namespace Statsd\Client;

abstract class Command implements CommandInterface
{
    protected function prepare($stat, $value, $rate=1)
    {
        if($rate < 1 ) {
            if($this->genRand() < $rate){
                $value = sprintf('%s|@%s', $value, $rate);
            } else {
                return;
            }
        }
        return sprintf('%s:%s', $stat, $value);
    }

    public function genRand()
    {
        return (float)mt_rand()/(float)mt_getrandmax();
    }
}
