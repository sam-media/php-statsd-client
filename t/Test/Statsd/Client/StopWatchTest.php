<?php
namespace Test\Statsd\Client;

use Statsd\Client\StopWatch;
use Statsd\Client;

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

    public function testSendDurationSinceReference()
    {
        $self = $this;

        $sockMock = $this->mockConnection();
        $sockMock->expects($this->once())->method('send')
            ->with(
                $this->matchesRegularExpression('/^foo\.bar\:\d{1,2}\|ms/')
            );

        $client =  new Client(array('connection' => $sockMock));

        $stopWatch = new StopWatch($client);
        $stopWatch->send('foo.bar');
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
