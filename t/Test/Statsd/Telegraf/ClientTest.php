<?php
namespace Test\Statsd\Telegraf;

use Statsd\Telegraf\Client;
use Statsd\Telegraf\Client\Command\Counter;
use Statsd\Telegraf\Client\Command\Timer;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testClientDefaultSettings()
    {
        $client = new Client();
        $this->assertEquals('', $client->getPrefix());

        $allSettings = $client->getSettings();
        $this->assertFalse($allSettings['throw_exception']);
        $this->assertEquals(array(), $allSettings['default_tags']);
    }

    /**
     * @dataProvider provideExpectedSettingsAndConstructorParams
     */
    public function testClientSettingsOverrideDefaults(
        array $expectedSettings,
        array $constructorSettings
    )
    {
        $client = new Client($constructorSettings);
        $this->assertEquals($expectedSettings, $client->getSettings());
    }

    /**
     * @return array
     */
    public function provideExpectedSettingsAndConstructorParams()
    {
        $defaultSettings = array(
            'throw_exception' => false,
            'default_tags' => array(),
            'connection' => null,
            'prefix' => ''
        );

        $prefixSettings = $defaultSettings;
        $prefixSettings['prefix'] = 'test';
        $prefixParams = array('prefix' => 'test');

        $tagsAndExceptionsSettings = $defaultSettings;
        $tagsAndExceptionsSettings['default_tags'] = array('region' => 'world');
        $tagsAndExceptionsSettings['throw_exception'] = true;
        $tagsAndExceptionsParams = array('default_tags' => array('region' => 'world'), 'throw_exception' => true);

        return array(
            'empty params' => array($defaultSettings, array()),
            'prefix params' => array($prefixSettings, $prefixParams),
            'tags and throw_exception params' => array($tagsAndExceptionsSettings, $tagsAndExceptionsParams),
        );
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Call to undefined method Statsd\Telegraf\Client::fooFunc()
     */
    public function testClientWithWrongCommand()
    {
        $client = new Client();
        $client->fooFunc("foo", "bar");
    }

    public function testClientAcceptsAdditionalCommands()
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->any())
            ->method('send')
            ->with("_foo.bar_:1003|xyz");

        $commandMock = $this->getMock('\\Statsd\\Telegraf\\Client\\Command\\Counter', array('incr'));
        $commandMock->expects($this->once())
            ->method('incr')
            ->with('foo.bar', 1003, 1)
            ->will($this->returnValue('_foo.bar_:1003|xyz'));

        $client = new Client(array('connection' => $socketMock));
        $client->addCommand($commandMock);

        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->incr('foo.bar', 1003, 1)
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage DUMMY ERROR IN TESTS
     */
    public function testClientReportsExceptionsFromCommands()
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->never())
            ->method('send');

        $commandMock = $this->getMock(
            '\\Statsd\\Telegraf\\Client\\Command\\Counter',
            array('decr')
        );
        $commandMock->expects($this->once())
            ->method('decr')
            ->with('foo.bar', 17, 1)
            ->will(
                $this->throwException(
                    new \RuntimeException("DUMMY ERROR IN TESTS")
                )
            );

        $client = new Client(array('connection' => $socketMock, 'throw_exception' => true));
        $client->addCommand($commandMock);

        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->decr('foo.bar', 17, 1)
        );
    }

    public function testClientSupportsFluentApi_WithAllStandardCommands()
    {
        $socketMock = $this->mockSocketConnection();
        $client = new Client(array('connection' => $socketMock));

        $result = $client->incr('foo.bar')
                        ->decr('foo.bar')
                        ->timing('db.query', 1)
                        ->set('ip.address', '127.0.0.1')
                        ->gauge('cpu_percent', 10)
                        ->gauge('cpu_percent', -2, true);

        $this->assertInstanceOf('\\Statsd\\Telegraf\\Client', $result);
    }

    private function mockCommand()
    {
        return $this->getMockForAbstractClass(
            '\\Statsd\\Client\\CommandInterface'
        );
    }

    private function mockSocketConnection()
    {
        return $this->getMock(
            '\\Statsd\\Client\\SocketConnection',
            array('send'),
            array(
                array(
                    'throw_exception' => false,
                    'host' => 'foo.bar',
                )
            )
        );
    }
}
