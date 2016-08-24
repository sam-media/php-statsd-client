<?php
namespace Test\Statsd\Telegraf\Client\Command;

use Statsd\Telegraf\Client\Command\Counter;


class CounterTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsCommandInterface()
    {
        $impl = new Counter();
        $this->assertInstanceOf('\\Statsd\\Client\\CommandInterface', $impl);
    }

    public function testDefaultTagsIsEmptyByDefault()
    {
        $impl = new Counter();
        $this->assertEquals(
            array(),
            $impl->getDefaultTags()
        );
    }

    public function testSetAndGetDefaultTags()
    {
        $tags = array('name1' => 'value1', 'name2' => 'value2');
        $impl = new Counter();
        $this->assertEquals(
            $tags,
            $impl->setDefaultTags($tags)->getDefaultTags()
        );
    }

    public function testGetCommandsReturnsArrayOfMethodNames()
    {
        $impl = new Counter();
        $commands = $impl->getCommands();
        $this->assertInternalType('array', $commands);
        $this->assertNotEmpty($commands);
        foreach ($commands as $command)
        {
            $this->assertInternalType('callable', array($impl, $command));
        }
    }

    /**
     * @dataProvider provideParametersAndExpectedResultForIncr
     */
    public function testIncrCreatesProperOutputWhenSampleRateMatches($expected, $stat, $count, $rate, array $tags)
    {
        $impl = new Counter();
        $this->assertEquals($expected, $impl->incr($stat, $count, $rate, $tags));
    }

    /**
     * @return array
     */
    public static function provideParametersAndExpectedResultForIncr()
    {
        return array(
            'count 1 no tags' => array('db.query:1|c', 'db.query', 1, 1, array()),
            'count 2, 1 tag' => array('foo.bar,region=world:2|c', 'foo.bar', 2, 1, array('region' => 'world')),
            'count 101, multiple tags' => array(
                'long.metric.name,region=world,severity=low:101|c',
                'long.metric.name',
                101,
                1,
                array('region' => 'world', 'severity' => 'low')
            ),
        );
    }

    public function testIncrUsesDefaultTagsIfNoTagsIsSpecified()
    {
        $impl = new Counter();
        $impl->setDefaultTags(array('tag1' => 'val1', 'tag2' => 'val2'));
        $this->assertEquals('foo.bar,tag1=val1,tag2=val2:4|c', $impl->incr('foo.bar', 4, 1));
    }

    public function testIncrIncludesSampleRateInResult()
    {
        $implMock = $this->mockCounter(array('genRand'));
        $implMock->expects($this->once())
                ->method('genRand')
                ->will($this->returnValue(0.45)
        );

        $this->assertEquals(
            'foo.bar:1|c|@0.6',
            $implMock->incr('foo.bar', 1, 0.6)
        );
    }

    public function testIncrReturnsEmptyStringWhenSampleRateIsLow()
    {
        $implMock = $this->mockCounter(array('genRand'));
        $implMock->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.85)
        );
        $this->assertNull($implMock->incr('foo.bar', 1 , 0.5));
    }

    /**
     * @dataProvider provideParametersAndExpectedResultForDecr
     */
    public function testDecrCreatesProperOutputWhenSampleRateMatches($expected, $stat, $count, $rate, array $tags)
    {
        $impl = new Counter();
        $this->assertEquals($expected, $impl->decr($stat, $count, $rate, $tags));
    }

    /**
     * @return array
     */
    public static function provideParametersAndExpectedResultForDecr()
    {
        return array(
            'count 1 no tags' => array('db.query:-1|c', 'db.query', 1, 1, array()),
            'count 2, 1 tag' => array('foo.bar,region=world:-2|c', 'foo.bar', 2, 1, array('region' => 'world')),
            'count 103, multiple tags' => array(
                'long.metric.name,region=world,severity=low:-103|c',
                'long.metric.name',
                103,
                1,
                array('region' => 'world', 'severity' => 'low')
            ),
        );
    }

    public function testDecrUsesDefaultTagsIfNoTagsIsSpecified()
    {
        $impl = new Counter();
        $impl->setDefaultTags(array('tag1' => 'val1', 'tag2' => 'val2'));
        $this->assertEquals('foo.bar,tag1=val1,tag2=val2:-5|c', $impl->decr('foo.bar', 5, 1));
    }

    public function testDecrIncludesSampleRateInResult()
    {
        $implMock = $this->mockCounter(array('genRand'));
        $implMock->expects($this->once())
                ->method('genRand')
                ->will($this->returnValue(0.45)
        );

        $this->assertEquals(
            'foo.bar:-2|c|@0.6',
            $implMock->decr('foo.bar', 2, 0.6)
        );
    }

    public function testDecrReturnsEmptyStringWhenSampleRateIsLow()
    {
        $implMock = $this->mockCounter(array('genRand'));
        $implMock->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.85)
        );
        $this->assertNull($implMock->decr('foo.bar', 2, 0.5));
    }

    private function mockCounter(array $methods=array())
    {
        $implMock = $this->getMock(
            '\\Statsd\\Telegraf\\Client\\Command\\Counter',
            $methods
        );
        return $implMock;
    }
}
