<?php
namespace Test\Statsd\Client;

use Statsd\Client\RelativeTimer;
use Statsd\Client;

class RelativeTimerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $client = new Client();
        $timer = new RelativeTimer($client, 1500);
        $this->assertSame($client, $timer->getClient());
        $this->assertEquals(1500, $timer->getReferenceTimestamp());
    }

    public function testDefaultReferenceIsNow()
    {
        $testStart = microtime(true);
        $timer = new RelativeTimer(new Client());
        $this->assertGreaterThanOrEqual($testStart, $timer->getReferenceTimestamp());
    }

    public function testSendReturnsTimerSelfReferenceForFluentApi()
    {
        $timer = new RelativeTimer($this->createClientWithMockedSocket());
        $this->assertSame($timer, $timer->send('foo.bar'));
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
