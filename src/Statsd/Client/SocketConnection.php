<?php
namespace Statsd\Client;

class SocketConnection implements ConnectionInterface
{
    public function __construct($settings=array())
    {
        $this->settings = array_merge(
            array(
                'host' => '127.0.0.1',
                'port' => '8125',
                'throw_exception' => false,
            ),
            $settings
        );
        $this->openSocket();
    }

    public function openSocket()
    {
        try {
            $this->socket = $this->fsockopen(
                sprintf(
                    "udp://%s",
                    $this->settings['host']
                ),
                $this->settings['port']
            );
        } catch (\Exception $e) {
            if($this->settings['throw_exception'] == true) {
                throw $e;
            }
            $this->socket = null;
        }
    }

    private function fsockopen($host, $port)
    {
        return fsockopen($host, $port);
    }

    public function send($string)
    {
        try {
            if (trim($string)) {
                return $this->fwrite($this->socket, $string);
            }
        } catch (\Exception $e) {
            if($this->settings['throw_exception'] == true) {
                throw $e;
            }
        }
        return 0;
    }

    public function fwrite($socket, $string)
    {
        return @fwrite($socket, $string);
    }
}
