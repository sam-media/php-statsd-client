<?php
namespace Test\Statsd\Telegraf\Client;

use Statsd\Telegraf\Client\StopWatch;
use Statsd\Telegraf\Client;

class StopWatchTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $client = new Client();
        $stopWatch = new StopWatch($client, 1500);
        $this->assertSame($client, $stopWatch->getClient());
        $this->assertEquals(1500, $stopWatch->getReferenceTimestamp());
    }

    public function testDefaultReferenceIsNow()
    {
        $testStart = microtime(true);
        $stopWatch = new StopWatch(new Client());
        $this->assertGreaterThanOrEqual($testStart, $stopWatch->getReferenceTimestamp());
    }

    /**
     * @dataProvider provideTagsAndExpectedRegex
     */
    public function testSendDurationSinceReference(
        array $defaultTags,
        array $tags,
        $metric,
        $expectedRegex
    )
    {
        $sockMock = $this->mockConnection();
        $sockMock->expects($this->once())->method('send')
            ->with(
                $this->matchesRegularExpression($expectedRegex)
            );

        $client =  new Client(array('connection' => $sockMock, 'default_tags' => $defaultTags));
        $stopWatch = new StopWatch($client);
        $stopWatch->send($metric, 1, $tags);
    }

    public static function provideTagsAndExpectedRegex()
    {
        $metric = 'foo.bar';

        return array(
            'no tags' => array(array(), array(), $metric, '/foo.bar:\d{1,3}\|ms/'),
            'with tags' => array(array(), array('t1' => 'v1'), $metric, '/foo.bar,t1=v1:\d{1,3}\|ms/'),
            'with default tags' => array(array('region' => 'world'), array(), $metric, '/foo.bar,region=world:\d{1,3}\|ms/'),
            'with tags and default tags' => array(array('region' => 'world'), array('t1' => 'v1'), $metric, '/foo.bar,region=world,t1=v1:\d{1,3}\|ms/'),
        );
    }

    public function testSendReturnsSelfReferenceForFluentApi()
    {
        $stopWatch = new StopWatch($this->createClientWithMockedSocket());
        $this->assertSame($stopWatch, $stopWatch->send('foo.bar'));
    }

    private function createClientWithMockedSocket()
    {
        return new Client(array('connection' => $this->mockConnection()));
    }

    private function mockConnection()
    {
        return $this->getMock(
            '\Statsd\Client\SocketConnection',
            array(
                'send'
            ),
            array(
                array(
                    'throw_exception' => false,
                    'host' => 'foo.bar',
                )
            )
        );
    }
}
