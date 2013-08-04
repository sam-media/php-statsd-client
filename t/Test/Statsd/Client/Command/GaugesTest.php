<?php
namespace Test\Statsd\Client\Command;

class GaugeTest extends \PHPUnit_Framework_TestCase
{
    public function testObject()
    {
        $inc = new \Statsd\Client\Command\Gauge();
        $this->assertEquals(
            array('gauge'),
            $inc->getCommands()
        );
    }

    public function testCheckMethodsExistence()
    {
        $inc = new \Statsd\Client\Command\Gauge();
        $class = new \ReflectionClass('\Statsd\Client\Command\Gauge');
        foreach($inc->getCommands() as $cmd){
            $method = $class->getMethod($cmd);
        }
    }

    public function testGauge()
    {
        $inc = new \Statsd\Client\Command\Gauge();
        $this->assertEquals(
            'foo.bar:40|g',
            $inc->gauge('foo.bar', 40)
        );
    }

    public function testGaugeWithDelta()
    {
        $inc = new \Statsd\Client\Command\Gauge();
        $this->assertEquals(
            'foo.bar:+40|g',
            $inc->gauge('foo.bar', 40, true)
        );
    }
}
