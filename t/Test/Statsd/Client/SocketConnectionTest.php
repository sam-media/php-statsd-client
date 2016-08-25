<?php
namespace Test\Statsd\Client;

class SocketConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testObject()
    {
        $sc = new \Statsd\Client\SocketConnection(
            array(
                'host' => '127.0.0.1',
                'port' => '8125',
                'throw_exception' => false,
            )
        );
    }

    public function testSuccessfulSendMessage()
    {
        $sc = new \Statsd\Client\SocketConnection();
        $this->assertEquals(
            6,
            $sc->send("123456")
        );
    }

    public function testFailSendMessage()
    {
        $sc = new \Statsd\Client\SocketConnection(
            array(
                'host' => 'host-foo.bar',
            )
        );
        $this->assertEquals(
            0,
            $sc->send("123456")
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage failed
     */
    public function testExceptioOpenSocket()
    {
        $this->markTestSkipped(
            "Throwing exceptions on socket connection is not consistent between HHVM and PHP"
            . "Even PHP documentation says detecting connection errors is not gruaranteed for UDP sockets"
        );
        $sc = $this->getMock(
            '\Statsd\Client\SocketConnection',
            array(
                'fsockopen'
            ),
            array(
                array(
                    'throw_exception' => true,
                    'host' => 'host-foo.bar',
                )
            )
        );

        //$sc->openSocket();
    }

    public function testExceptionSendMessageWithoutThrowingIt()
    {
        $sc = $this->getMock(
            '\Statsd\Client\SocketConnection',
            array(
                'fwrite'
            )
        );

        $sc->expects($this->any())
            ->method('fwrite')
            ->will($this->throwException(new \Exception)
        );

        $this->assertEquals(
            0,
            $sc->send("123456")
        );
    }

    /**
     * @expectedException Exception
     */
    public function testExceptionSendMessage()
    {
        $sc = $this->getMock(
            '\Statsd\Client\SocketConnection',
            array(
                'fwrite'
            ),
            array(
                array('throw_exception' => true)
            )
        );

        $sc->expects($this->any())
            ->method('fwrite')
            ->will($this->throwException(new \Exception)
        );

        $this->assertEquals(
            0,
            $sc->send("123456")
        );
    }
}
