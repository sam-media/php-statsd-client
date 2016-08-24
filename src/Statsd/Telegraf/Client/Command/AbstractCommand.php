<?php
namespace Statsd\Telegraf\Client\Command;

use Statsd\Client\CommandInterface;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var array       associative array of tag name => value
     */
    protected $defaultTags = array();

    /**
     * @return array
     */
    abstract public function getCommands();

    /**
     * @param array       associative array of tag name => value
     * @return \Statsd\Telegraf\Client\Command\AbstractCommand self reference
     */
    public function setDefaultTags(array $tags)
    {
        $this->defaultTags = $tags;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultTags()
    {
        return $this->defaultTags;
    }

    /**
     * @param string $stat
     * @param int    $value
     * @param float  $rate
     * @param array  $tags      associative array of tag name => values
     * @return string|null
     */
    protected function prepare($stat, $value, $rate, array $tags=array())
    {
        if ($rate < 1) {
            if ($this->genRand() >= $rate) {
                return null;
            }
            $value = sprintf('%s|@%s', $value, $rate);
        }
        return sprintf('%s:%s', $this->getStatWithTags($stat, $tags), $value);
    }

    /**
     * @param string $stat
     * @param array  $tags      associative array of tag name => values
     * @return string
     */
    protected function getStatWithTags($stat, array $statTags=array())
    {
        $tags = $statTags ? $statTags : $this->getDefaultTags();
        if (!$tags) {
            return $stat;
        }
        $chunks = array($stat);
        foreach ($tags as $key => $value) {
            $chunks[] = sprintf("%s=%s", $key, $value);
        }
        return implode(',', $chunks);
    }

    /**
     * @return float
     */
    protected function genRand()
    {
        return (float) mt_rand() / (float) mt_getrandmax();
    }
}
