<?php
namespace Test\Statsd;

use Exception;
use Statsd\Client;
use Statsd\Client\Command\Counter;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testClientWithDefaultSettings()
    {
        $statsd = new Client();
        $this->assertEquals(
            '',
            $statsd->getPrefix()
        );

        $allSettings = $statsd->getSettings();
        $this->assertFalse(
            $allSettings['throw_exception']
        );
    }

    public function testClientWithOverideSettings()
    {
        $statsd = new Client(
            array(
                'prefix' => 'foo.bar',
            )
        );

        $this->assertEquals(
            'foo.bar',
            $statsd->getPrefix()
        );
    }

    public function testClientWithWrongCommand()
    {
        $statsd = new Client();
        $this->setExpectedException(
            '\BadFunctionCallException',
            'Call to undefined method Statsd\Client::fooFunc()'
        );
        $statsd->fooFunc("foo", "bar");
    }

    public function getMockUpSocketConnection()
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

    public function testClientAddCommand()
    {
        $sc = $this->getMockUpSocketConnection();
        $sc->expects($this->once())
            ->method('send')
            ->with("foo.bar:1|c");

        $statsd = new Client(array('connection' => $sc));
        $statsd->addCommand(
            new Counter()
        );

        $this->assertInstanceOf(
            '\Statsd\Client',
            $statsd->incr('foo.bar', 1)
        );
    }

    public function testClientCallCommandWithPrefix()
    {
        $sc = $this->getMockUpSocketConnection();
        $sc->expects($this->once())
            ->method('send')
            ->with("top.foo.bar:1|c");

        $statsd = new Client(array('connection' => $sc));
        $statsd->addCommand(
            new Counter()
        );
        $statsd->setPrefix('top');

        $this->assertInstanceOf(
            '\Statsd\Client',
            $statsd->incr('foo.bar', 1)
        );
    }

    public function testClientCallCommandWithException()
    {
        $cmd = $this->getMock(
            '\Statsd\Client\Command\Counter',
            array('incr')
        );

        $cmd->expects($this->once())
            ->method('incr')
            ->will($this->throwException(new Exception("DUMMY EXCEPTION")));

        $statsd = new Client(array('throw_exception'=> true));
        $statsd->addCommand($cmd);

        $this->setExpectedException('Exception', 'DUMMY EXCEPTION');
        $statsd->__call('incr', array('foo.bar', 1));
    }

    public function testChaingCall()
    {
        $statsd = new Client();
        $result = $statsd->incr('foo.bar')
            ->decr('foo.bar')
            ->gauge('foo.bar', 10);

        $this->assertInstanceOf(
            '\Statsd\Client',
            $result
        );
    }

    public function testCreateStopWatchUsesClientToSendMetrics()
    {
        $now = microtime(true);
        $socketMock = $this->getMockUpSocketConnection();
        $socketMock->expects($this->once())
            ->method('send')
            ->with($this->matchesRegularExpression('/^foo.bar:\d+\|ms$/'));

        $statsd = new Client(array('connection' => $socketMock));
        $stopWatch = $statsd->createStopWatch($now);
        $this->assertInstanceOf('\Statsd\Client\StopWatch', $stopWatch);
        $this->assertEquals($now, $stopWatch->getReferenceTimestamp());
        $this->assertSame($statsd, $stopWatch->getClient());

        $stopWatch->send('foo.bar');
    }
}
