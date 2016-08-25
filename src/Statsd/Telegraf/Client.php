<?php
namespace Statsd\Telegraf;

use BadMethodCallException;
use Statsd\Telegraf\Client\Command\Counter;
use Statsd\Telegraf\Client\Command\Set;
use Statsd\Telegraf\Client\Command\Timer;
use Statsd\Telegraf\Client\Command\Gauge;


class Client extends \Statsd\Client
{
    /**
     * Returns associative array of deafult settings.
     *
     * @return array
     */
    protected static function getDefaultSettings()
    {
        return array(
                    'prefix' => '',
                    'throw_exception' => false,
                    'connection' => null,
                    'default_tags' => array()
                );
    }

    /**
     * Return associative array of default tags applied for all metrics
     * without specific tags.
     *
     * @return array
     */
    public function getDefaultTags()
    {
        return $this->settings['default_tags'];
    }

    protected function registerCommands()
    {
        $tags = $this->getDefaultTags();

        $commands = array(
           new Counter(),
           new Set(),
           new Timer(),
           new Gauge(),
        );

        foreach ($commands as $command) {
            $command->setDefaultTags($tags);
            $this->addCommand($command);
        }
    }

    public function __call($name, $args)
    {
        if (!array_key_exists($name, $this->commands) ) {
            throw new BadMethodCallException(
                sprintf(
                    "Call to undefined method %s::%s()",
                    get_class($this),
                    $name
                )
            );
        }
        return parent::__call($name, $args);
    }
}
