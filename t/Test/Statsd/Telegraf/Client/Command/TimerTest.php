<?php
namespace Test\Statsd\Telegraf\Client\Command;

use Statsd\Telegraf\Client\Command\Timer;


class TimerTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsCommandInterface()
    {
        $impl = new Timer();
        $this->assertInstanceOf('\\Statsd\\Client\\CommandInterface', $impl);
    }

    public function testDefaultTagsIsEmptyByDefault()
    {
        $impl = new Timer();
        $this->assertEquals(
            array(),
            $impl->getDefaultTags()
        );
    }

    public function testSetAndGetDefaultTags()
    {
        $tags = array('name1' => 'value1', 'name2' => 'value2');
        $impl = new Timer();
        $this->assertEquals(
            $tags,
            $impl->setDefaultTags($tags)->getDefaultTags()
        );
    }

    public function testGetCommandsReturnsArrayOfMethodNames()
    {
        $impl = new Timer();
        $commands = $impl->getCommands();
        $this->assertInternalType('array', $commands);
        $this->assertNotEmpty($commands);
        foreach ($commands as $command)
        {
            $this->assertInternalType('callable', array($impl, $command));
        }
    }

    /**
     * @dataProvider provideParametersAndExpectedResultForTiming
     */
    public function testTimingCreatesProperOutputWhenSampleRateMatches($expected, $stat, $delta, $rate, array $tags)
    {
        $impl = new Timer();
        $this->assertEquals($expected, $impl->timing($stat, $delta, $rate, $tags));
    }

    /**
     * @return array
     */
    public static function provideParametersAndExpectedResultForTiming()
    {
        return array(
            'delta 1 no tags' => array('db.query:1|ms', 'db.query', 1, 1, array()),
            'detla 10, 1 tag' => array('foo.bar,region=world:10|ms', 'foo.bar', 10, 1, array('region' => 'world')),
            'count 101, multiple tags' => array(
                'long.metric.name,region=world,severity=low:101|ms',
                'long.metric.name',
                101,
                1,
                array('region' => 'world', 'severity' => 'low')
            ),
        );
    }

    public function testTimingUsesDefaultTagsIfNoTagsIsSpecified()
    {
        $impl = new Timer();
        $impl->setDefaultTags(array('tag1' => 'val1', 'tag2' => 'val2'));
        $this->assertEquals('foo.bar,tag1=val1,tag2=val2:3|ms', $impl->timing('foo.bar', 3, 1));
    }

    public function testTimingAcceptsAnObjectMethodToCallAndTimeTheExecution()
    {
        $deprecatedWarningGenerated = false;
        $setDeprecatedWarning = function () use (&$deprecatedWarningGenerated) {
            $deprecatedWarningGenerated = true;
        };
        set_error_handler($setDeprecatedWarning, E_USER_DEPRECATED);

        $timer = new Timer();
        $metric = $timer->timing('test.method.sleep', array($this, 'sleepABit'), 1);

        restore_error_handler();

        $regex = '/test.method.sleep:(?<elapsed>\d+)\|ms/';
        $this->assertRegExp($regex, $metric);
        preg_match($regex, $metric, $matches);
        $this->assertGreaterThan(0, $matches['elapsed']);
        $this->assertTrue($deprecatedWarningGenerated);
    }

    public function sleepABit()
    {
        usleep(1000);
    }

    public function testTimingAcceptsAClassStaticMethodToCallAndTimeTheExecution()
    {
        $deprecatedWarningGenerated = false;
        $setDeprecatedWarning = function () use (&$deprecatedWarningGenerated) {
            $deprecatedWarningGenerated = true;
        };
        set_error_handler($setDeprecatedWarning, E_USER_DEPRECATED);

        $impl = new Timer();
        $metric = $impl->timing(
            'test.static.method.sleep',
            array('\\Test\\Statsd\\Telegraf\\Client\\Command\\TimerTest', 'staticallySleepABit'),
            1
        );

        restore_error_handler();

        $regex = '/test.static.method.sleep:(?<elapsed>\d+)\|ms/';

        $this->assertRegExp($regex, $metric);
        preg_match($regex, $metric, $matches);
        $this->assertGreaterThan(0, $matches['elapsed']);
        $this->assertTrue($deprecatedWarningGenerated);
    }

    public static function staticallySleepABit()
    {
        usleep(1000);
    }

    public function testTimingAcceptsAClosureToCallAndTimeTheExecution()
    {
        $deprecatedWarningGenerated = false;
        $setDeprecatedWarning = function () use (&$deprecatedWarningGenerated) {
            $deprecatedWarningGenerated = true;
        };
        set_error_handler($setDeprecatedWarning, E_USER_DEPRECATED);

        $sleepABit = function () { usleep(1000); };

        $impl = new Timer();
        $metric = $impl->timing('test.closure.sleep', $sleepABit, 1);

        $regex = '/test.closure.sleep:(?<elapsed>\d+)\|ms/';
        $this->assertRegExp($regex, $metric);
        preg_match($regex, $metric, $matches);
        $this->assertGreaterThan(0, $matches['elapsed']);
        $this->assertTrue($deprecatedWarningGenerated);
    }

    public function testTimingIncludesSampleRateInResult()
    {
        $implMock = $this->mockTimer(array('genRand'));
        $implMock->expects($this->once())
                ->method('genRand')
                ->will($this->returnValue(0.45)
        );

        $this->assertEquals(
            'foo.bar:1|ms|@0.6',
            $implMock->timing('foo.bar', 1, 0.6)
        );
    }

    public function testTimingReturnsNullWhenSampleIsDiscarded()
    {
        $implMock = $this->mockTimer(array('genRand'));
        $implMock->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.85)
        );
        $this->assertNull($implMock->timing('foo.bar', 1 , 0.5));
    }

    /**
     * @dataProvider provideCallableValues
     */
    public function testTimeCallableWitoutTags($callable)
    {
        $timer = new Timer();
        $result = $timer->timeCallable('foo.bar', $callable, 1);
        $this->assertRegExp('/foo.bar:\d+\|ms/', $result);
    }

    /**
     * @dataProvider provideCallableValues
     */
    public function testTimeCallableWithTags($callable)
    {
        $timer = new Timer();
        $result = $timer->timeCallable('foo.bar', $callable, 1, array('region' => 'world'));
        $this->assertRegExp('/foo.bar,region=world:\d+\|ms/', $result);
    }

    /**
     * @dataProvider provideCallableValues
     */
    public function testTimeCallableWithDefaultTags($callable)
    {
        $timer = new Timer();
        $timer->setDefaultTags(array('tag1' => 'val1', 'tag2' => 'val2'));
        $result = $timer->timeCallable('foo.bar', $callable, 1);
        $this->assertRegExp('/foo.bar,tag1=val1,tag2=val2:\d+\|ms/', $result);
    }

    /**
     * @dataProvider provideCallableValues
     */
    public function testTimeCallableWithDefaultTagsAndMetricTags($callable)
    {
        $timer = new Timer();
        $timer->setDefaultTags(array('tag1' => 'val1', 'region' => 'world'));
        $result = $timer->timeCallable('foo.bar', $callable, 1, array('pri'=>'low'));
        $this->assertRegExp('/foo.bar,tag1=val1,region=world,pri=low:\d+\|ms/', $result);
    }

    public function provideCallableValues()
    {
        $simpleClosure = function () { usleep(1); };

        return array(
            'function name string' => array('time'),
            'closure' => array($simpleClosure),
            'object method array' => array(array($this, 'sleepABit')),
            'class method array' => array(
                array('\\Test\\Statsd\\Telegraf\\Client\\Command\\TimerTest', 'staticallySleepABit')
            ),
        );
    }

    public function testTimeCallableReturnsNullWhenSampleIsDiscarded()
    {
        $implMock = $this->mockTimer(array('genRand'));
        $implMock->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.85)
        );
        $this->assertNull($implMock->timeCallable('foo.bar', 'time', 0.5));
    }

    private function mockTimer(array $methods=array())
    {
        $implMock = $this->getMock(
            '\\Statsd\\Telegraf\\Client\\Command\\Timer',
            $methods
        );
        return $implMock;
    }
}
