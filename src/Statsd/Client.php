<?php
namespace Statsd;
          
class Client
{

    protected $commands = array();

    public function __construct(array $settings=array())
    {
        $this->settings = array_merge(
            array(
                'prefix' => '',
                'throw_exception' => false,
                'connection' => null,
            ),
            $settings
        );
        if($this->settings['connection'] == null){
            $this->connection = new Client\SocketConnection($this->settings);
        } else {
            $this->connection = $this->settings['connection'];
        }
        $this->registerCommands();
    }

    private function registerCommands()
    {
        $commands = array(
           '\Statsd\Client\Command\Counter',
           '\Statsd\Client\Command\Set',
           '\Statsd\Client\Command\Timer',
           '\Statsd\Client\Command\Gauge',
        );

        foreach($commands as $cmd) {
            $this->addCommand(new $cmd);
        }
    }

    public function addCommand($cmd_obj)
    {
        $class = new \ReflectionObject($cmd_obj);
        if(!$class->implementsInterface('\Statsd\Client\CommandInterface')) {
            throw new \InvalidArgumentException(
                sprintf(
                    "%s::addCommand() accept class that implements CommandInterface",
                    __CLASS__
                )
            );
        }

        foreach($cmd_obj->getCommands() as $cmd){
            $this->commands[$cmd] = $cmd_obj;
        }
    }

    public function __call($name, $arguments)
    {
        if(!array_key_exists($name, $this->commands)) {
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
            if(trim($this->getPrefix())){
                $command = sprintf(
                    "%s.%s",
                    $this->getPrefix(),
                    $command
                );
            }
            $this->connection->send($command);
        } catch(\Exception $e) {
            if($this->settings['throw_exception'] == true) {
                throw $e;
            }
        }
        return $this;
    }

    private function callCommand($name, $arguments)
    {
        $cmd_obj = $this->commands[$name];
        return call_user_func_array(
            array($cmd_obj, $name),
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
}
