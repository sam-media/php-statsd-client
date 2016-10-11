<?php
namespace Test\Statsd\Client\Command;

use DateTime;
use ReflectionClass;
use Statsd\Client\Command\Timer;

class TimerTest extends \PHPUnit_Framework_TestCase
{
    public function testObject()
    {
        $timer = new Timer();
        $this->assertEquals(
            array('timing', 'timingSince', 'timeCallable'),
            $timer->getCommands()
        );
    }

    public function testCheckMethodsExistence()
    {
        $timer = new Timer();
        $class = new ReflectionClass('\Statsd\Client\Command\Timer');
        foreach ($timer->getCommands() as $cmd) {
            $method = $class->getMethod($cmd);
        }
    }

    public function testTiming()
    {
        $timer = new Timer();
        $this->assertEquals(
            'foo.bar:10|ms',
            $timer->timing('foo.bar', 10)
        );
    }

    public function testTimingWithClosure()
    {
        $timer = new Timer();
        $result = $timer->timing(
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

    public function testTimingSince()
    {
        $start = time();
        $timer = new Timer();
        $this->assertRegExp(
            '/foo\.bar\:\d+\|ms/',
            $timer->timingSince('foo.bar', $start)
        );
    }

    /**
     * @dataProvider provideCallableValues
     */
    public function testTimeCallable($callable)
    {
        $timer = new Timer();
        $result = $timer->timeCallable('foo.bar', $callable);
        $this->assertRegExp('/foo.bar:\d+|ms/', $result);
    }

    public function provideCallableValues()
    {
        $simpleClosure = function () { usleep(1); };

        return array(
            'function name string' => array('time'),
            'closure' => array($simpleClosure),
            'object method array' => array(array($this, 'sleep1MicroSecond')),
            'class method array' => array(array('\Test\Statsd\Client\Command\TimerTest', 'staticSleep1MicroSecond')),
        );
    }

    /**
     * This is to be called as an object method callable by
     * data provider for testTimeCallable.
     */
    public function sleep1MicroSecond()
    {
        usleep(1);
    }

    /**
     * This is to be called as a static method callable by
     * data provider for testTimeCallable.
     */
    public static function staticSleep1MicroSecond()
    {
        usleep(1);
    }

    /**
     * @dataProvider provideNoneCallableValues
     */
    public function testTimeCallableThrowsExceptionOnNoneCallableParams($noneCallable)
    {
        $timer = new Timer();
        $this->setExpectedException('\InvalidArgumentException');
        $timer->timeCallable('foo.bar', $noneCallable);
    }

    public static function provideNoneCallableValues()
    {
        $self = new self();
        return array(
            'integer' => array(100),
            'float' => array(302.455),
            'bool flase' => array(false),
            'bool true' => array(true),
            'string' => array('no such function exists'),
            'array' => array(array(1,2)),
            'object' => array($self),
            'class' => array('\PHPUnit_Framework_TestCase'),
            'object in array' => array(array($self)),
            'class in array' => array('\Test\Statsd\Client\Command\TimerTest')
        );
    }
}
