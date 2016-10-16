<?php
namespace Statsd;

use \Statsd\Client\CommandInterface;
use Statsd\Client\StopWatch;

class Client
{
    protected $commands = array();

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
                    'connection' => null
                );
    }

    public function __construct(array $settings=array())
    {
        $this->settings = array_merge(
            static::getDefaultSettings(),
            $settings
        );

        if ($this->settings['connection'] == null) {
            $this->connection = new Client\SocketConnection($this->settings);
        } else {
            $this->connection = $this->settings['connection'];
        }
        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $commands = array(
           '\Statsd\Client\Command\Counter',
           '\Statsd\Client\Command\Set',
           '\Statsd\Client\Command\Timer',
           '\Statsd\Client\Command\Gauge',
        );

        foreach ($commands as $cmd) {
            $this->addCommand(new $cmd);
        }
    }

    /**
     * @param \Statsd\Client\CommandInterface
     */
    public function addCommand(CommandInterface $cmdObj)
    {
        foreach ($cmdObj->getCommands() as $cmd) {
            $this->commands[$cmd] = $cmdObj;
        }
    }

    public function __call($name, $arguments)
    {
        if (!array_key_exists($name, $this->commands) ) {
            throw new \BadFunctionCallException(
                sprintf(
                    "Call to undefined method %s::%s()",
                    __CLASS__,
                    $name
                )
            );
        }
        try {
            $command = $this->callCommand($name, $arguments);
            if (!trim($command)) {
                return $this;
            }
            if (trim($this->getPrefix())) {
                $command = sprintf(
                    "%s.%s",
                    $this->getPrefix(),
                    $command
                );
            }
            $this->connection->send($command);
        } catch (\Exception $e) {
            if ($this->settings['throw_exception'] == true) {
                throw $e;
            }
        }
        return $this;
    }

    protected function callCommand($name, $arguments)
    {
        $cmdObj = $this->commands[$name];
        return call_user_func_array(
            array($cmdObj, $name),
            $arguments
        );
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getPrefix()
    {
        return $this->settings['prefix'];
    }

    public function setPrefix($prefix)
    {
        $this->settings['prefix'] = $prefix;
        return $this;
    }

    /**
     * Returns a StopWatch that can be used to send timing metrics
     * since a given time reference (now by default).
     *
     * @param int|null  $reference (default is now)
     * @return \Statsd\Client\StopWatch
     */
    public function createStopWatch($reference = null)
    {
        return new StopWatch($this, $reference);
    }
}
