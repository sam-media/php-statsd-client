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
        $impl = new Timer();
        $this->assertRegExp(
            '/test.method.sleep:\d+\|ms/',
            $impl->timing('test.method.sleep', array($this, 'sleepABit'), 1)
        );
    }

    public function sleepABit()
    {
        usleep(300000);
    }

    public function testTimingAcceptsAClassStaticMethodToCallAndTimeTheExecution()
    {
        $impl = new Timer();
        $this->assertRegExp(
            '/test.static.method.sleep:\d+\|ms/',
            $impl->timing(
                'test.static.method.sleep',
                array('\\Test\\Statsd\\Telegraf\\Client\\Command\\TimerTest', 'staticallySleepABit'),
                1
            )
        );
    }

    public static function staticallySleepABit()
    {
        usleep(300000);
    }

    public function testTimingAcceptsAClosureToCallAndTimeTheExecution()
    {
        $sleepABit = function () { usleep(300000); };
        $impl = new Timer();
        $this->assertRegExp(
            '/test.closure.sleep:\d+\|ms/',
            $impl->timing('test.closure.sleep', $sleepABit, 1)
        );
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

    private function mockTimer(array $methods=array())
    {
        $implMock = $this->getMock(
            '\\Statsd\\Telegraf\\Client\\Command\\Timer',
            $methods
        );
        return $implMock;
    }
}
