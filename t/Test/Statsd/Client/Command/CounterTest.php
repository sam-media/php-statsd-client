<?php
namespace Test\Statsd\Client\Command;

class CounterTest extends \PHPUnit_Framework_TestCase
{
    public function testObject()
    {
        $inc = new \Statsd\Client\Command\Counter();
        $this->assertEquals(
            array('incr', 'decr'),
            $inc->getCommands()
        );
    }

    public function testCheckMethodsExistence()
    {
        $inc = new \Statsd\Client\Command\Counter();
        $class = new \ReflectionClass('\Statsd\Client\Command\Counter');
        foreach($inc->getCommands() as $cmd){
            $method = $class->getMethod($cmd);
            $this->assertTrue(true, 'Just make sure previous was not thrown');
        }
    }

    public function testIncr()
    {
        $inc = new \Statsd\Client\Command\Counter();
        $this->assertEquals(
            'foo.bar:1|c',
            $inc->incr('foo.bar')
        );
    }

    public function testIncrByRate()
    {
        //How odd it would be if this test fails!
        $inc = new \Statsd\Client\Command\Counter();
        $this->assertNull(
            $inc->incr('foo.bar', 5, 0.000000000001)
        );
    }

    public function testIncrSendByRate()
    {
        $inc = $this->getMock(
            '\Statsd\Client\Command\Counter',
            array(
                'genRand'
            )
        );

        $inc->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.45)
        );

        $this->assertEquals(
            'foo.bar:1|c|@0.5',
            $inc->incr('foo.bar', 1 , 0.5)
        );
    }

    public function testIncrNullByRate()
    {
        $inc = $this->getMock(
            '\Statsd\Client\Command\Counter',
            array(
                'genRand'
            )
        );

        $inc->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.85)
        );

        $this->assertNull(
            $inc->incr('foo.bar', 1 , 0.5)
        );
    }

    public function testDecr()
    {
        $inc = new \Statsd\Client\Command\Counter();
        $this->assertEquals(
            'foo.bar:-1|c',
            $inc->decr('foo.bar')
        );
    }
}
