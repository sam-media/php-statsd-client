<?php
namespace Test\Statsd;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testClientWithDefaultSettings()
    {
        $statsd = new \Statsd\Client();
        $this->assertEquals(
            '',
            $statsd->getPrefix()
        );

        $all_settings = $statsd->getSettings();
        $this->assertFalse(
            $all_settings['throw_exception']
        );
    }

    public function testClientWithOverideSettings()
    {
        $statsd = new \Statsd\Client(
            array(
                'prefix' => 'foo.bar',
            )
        );

        $this->assertEquals(
            'foo.bar',
            $statsd->getPrefix()
        );
    }

    /**
     * @expectedException BadFunctionCallException
     * @expectedExceptionMessage Call to undefined method Statsd\Client::fooFunc()
     */
    public function testClientWithWrongCommand()
    {
        $statsd = new \Statsd\Client();
        $statsd->fooFunc("foo", "bar");
    }

    /**
     * @expectedException InvalidArgumentException 
     * @expectedExceptionMessage Statsd\Client::addCommand() accept class that implements CommandInterface
     */
    public function testClientWithWrongCommandObject()
    {
        $statsd = new \Statsd\Client();
        $statsd->addCommand(new \StdClass());
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

        $statsd = new \Statsd\Client(array('connection' => $sc));
        $statsd->addCommand(
            new \Statsd\Client\Command\Increment()
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

        $statsd = new \Statsd\Client(array('connection' => $sc));
        $statsd->addCommand(
            new \Statsd\Client\Command\Increment()
        );
        $statsd->setPrefix('top');
        
        $this->assertInstanceOf(
            '\Statsd\Client',
            $statsd->incr('foo.bar', 1)
        );
    }


    /**
     * @expectedException Exception
     * @expectedExceptionMessage DUMMY EXCEPTION
     */
    public function testClientCallCommandWithException()
    {
        $cmd = $this->getMock(
            '\Statsd\Client\Command\Increment',
            array('incr')
        );

        $cmd->expects($this->once())
            ->method('incr')
            ->will($this->throwException(new \Exception("DUMMY EXCEPTION")));

        $statsd = new \Statsd\Client(array('throw_exception'=> true));
        $statsd->addCommand($cmd);
        $statsd->__call('incr', array('foo.bar', 1));
    }

}
