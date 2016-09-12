<?php
namespace Test\Statsd\Telegraf\Client\Command;

use Statsd\Telegraf\Client\Command\Gauge;


class GaugeTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsCommandInterface()
    {
        $impl = new Gauge();
        $this->assertInstanceOf('\\Statsd\\Client\\CommandInterface', $impl);
    }

    public function testDefaultTagsIsEmptyByDefault()
    {
        $impl = new Gauge();
        $this->assertEquals(
            array(),
            $impl->getDefaultTags()
        );
    }

    public function testGaugeAndGetDefaultTags()
    {
        $tags = array('name1' => 'value1', 'name2' => 'value2');
        $impl = new Gauge();
        $this->assertEquals(
            $tags,
            $impl->setDefaultTags($tags)->getDefaultTags()
        );
    }

    public function testGetCommandsReturnsArrayOfMethodNames()
    {
        $impl = new Gauge();
        $commands = $impl->getCommands();
        $this->assertInternalType('array', $commands);
        $this->assertNotEmpty($commands);
        foreach ($commands as $command)
        {
            $this->assertInternalType('callable', array($impl, $command));
        }
    }

    /**
     * @dataProvider provideParametersAndExpectedResultForGauge
     */
    public function testGaugeCreatesProperOutputWhenSampleRateMatches($expected, $stat, $value, $rate, array $tags)
    {
        $impl = new Gauge();
        $this->assertEquals($expected, $impl->gauge($stat, $value, $rate, false, $tags));
    }

    /**
     * @return array
     */
    public static function provideParametersAndExpectedResultForGauge()
    {
        return array(
            'gauge cpu_percent no tags' => array('cpu_percent:53|g', 'cpu_percent', 53, 1, array()),
            'gauge mem_mb 1 tag' => array('mem_mb,region=world:132.5|g', 'mem_mb', 132.5, 1, array('region' => 'world')),
            'gauge running_queries, multiple tags' => array(
                'running_queries,region=world,severity=low:103|g',
                'running_queries',
                103,
                1,
                array('region' => 'world', 'severity' => 'low')
            ),
        );
    }

    /**
     * @dataProvider provideParametersAndExpectedResultForDeltaGauge
     */
    public function testGaugeCreatesProperOutputForDeltaGaugesWhenSampleRateMatches($expected, $stat, $value, $rate, array $tags)
    {
        $impl = new Gauge();
        $this->assertEquals($expected, $impl->gauge($stat, $value, $rate, true, $tags));
    }

    /**
     * @return array
     */
    public static function provideParametersAndExpectedResultForDeltaGauge()
    {
        return array(
            'gauge cpu_percent no tags' => array('cpu_percent:+6|g', 'cpu_percent', 6, 1, array()),
            'gauge mem_mb 1 tag' => array('mem_mb,region=world:+46.2|g', 'mem_mb', 46.2, 1, array('region' => 'world')),
            'gauge running_queries, multiple tags' => array(
                'running_queries,region=world,severity=low:-18|g',
                'running_queries',
                -18,
                1,
                array('region' => 'world', 'severity' => 'low')
            ),
        );
    }

    public function testGaugeUsesDefaultTagsIfNoTagsIsSpecified()
    {
        $impl = new Gauge();
        $impl->setDefaultTags(array('tag1' => 'val1', 'tag2' => 'val2'));
        $this->assertEquals('foo.bar,tag1=val1,tag2=val2:1000|g', $impl->gauge('foo.bar', 1000, 1));
    }

    public function testGaugeIncludesSampleRateInResult()
    {
        $implMock = $this->mockGauge(array('genRand'));
        $implMock->expects($this->once())
                ->method('genRand')
                ->will($this->returnValue(0.45)
        );

        $this->assertEquals(
            'foo.bar:20|g|@0.6',
            $implMock->gauge('foo.bar', 20, 0.6)
        );
    }

    public function testGaugeReturnsNullWhenSampleIsDiscarded()
    {
        $implMock = $this->mockGauge(array('genRand'));
        $implMock->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.85)
        );
        $this->assertNull($implMock->gauge('foo.bar', 30, 0.5));
    }

    private function mockGauge(array $methods=array())
    {
        $implMock = $this->getMock(
            '\\Statsd\\Telegraf\\Client\\Command\\Gauge',
            $methods
        );
        return $implMock;
    }
}
