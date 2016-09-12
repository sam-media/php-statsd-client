<?php
namespace Test\Statsd\Client\Command;

class TimerTest extends \PHPUnit_Framework_TestCase
{
    public function testObject()
    {
        $inc = new \Statsd\Client\Command\Timer();
        $this->assertEquals(
            array('timing'),
            $inc->getCommands()
        );
    }

    public function testCheckMethodsExistence()
    {
        $inc = new \Statsd\Client\Command\Timer();
        $class = new \ReflectionClass('\Statsd\Client\Command\Timer');
        foreach ($inc->getCommands() as $cmd) {
            $method = $class->getMethod($cmd);
        }
    }

    public function testTiming()
    {
        $inc = new \Statsd\Client\Command\Timer();
        $this->assertEquals(
            'foo.bar:10|ms',
            $inc->timing('foo.bar', 10)
        );
    }

    public function testTimingWithClosure()
    {
        $inc = new \Statsd\Client\Command\Timer();
        $result = $inc->timing(
            'foo.bar',
            function () {
                usleep(1000);
            }
        );
        $this->assertRegExp(
            '/foo.bar:\d+|ms/',
            $result
        );
    }
}
