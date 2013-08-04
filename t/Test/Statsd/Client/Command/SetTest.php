<?php
namespace Test\Statsd\Client\Command;

class SetTest extends \PHPUnit_Framework_TestCase
{
    public function testObject()
    {
        $inc = new \Statsd\Client\Command\Set();
        $this->assertEquals(
            array('set'),
            $inc->getCommands()
        );
    }

    public function testCheckMethodsExistence()
    {
        $inc = new \Statsd\Client\Command\Set();
        $class = new \ReflectionClass('\Statsd\Client\Command\Set');
        foreach($inc->getCommands() as $cmd){
            $method = $class->getMethod($cmd);
            $this->assertTrue(true, 'Just make sure previous was not thrown');
        }
    }

    public function testSet()
    {
        $inc = new \Statsd\Client\Command\Set();
        $this->assertEquals(
            'foo.bar:10|s',
            $inc->set('foo.bar', 10)
        );
    }

}
