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
        $this->assertTrue($allSettings['merge_tags']);
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
            'merge_tags' => true,
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

        $noMergeTagsSettings = $defaultSettings;
        $noMergeTagsSettings['merge_tags'] = false;
        $noMergeTagsParams = array('merge_tags' => false);

        return array(
            'empty params' => array($defaultSettings, array()),
            'prefix params' => array($prefixSettings, $prefixParams),
            'tags and throw_exception params' => array($tagsAndExceptionsSettings, $tagsAndExceptionsParams),
            'no merge tags' => array($noMergeTagsSettings, $noMergeTagsParams),
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

        $client->decr('foo.bar', 17, 1);
    }

    /**
     * @dataProvider provideParamsForIncrAndExpectedRequest
     */
    public function testClientSupportsIncrByDefault(
        $expectedRequest,
        array $defaultTags,
        $stat,
        $count,
        array $tags
    )
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())->method('send')->with($expectedRequest);

        $client = new Client(array('connection' => $socketMock, 'default_tags' => $defaultTags));
        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->incr($stat, $count, 1, $tags)
        );
    }

    public static function provideParamsForIncrAndExpectedRequest()
    {
        return array(
            'no tags' => array('event.login:1|c', array(), 'event.login', 1, array()),
            'with default tags' => array('event.good,region=world:3|c', array('region'=>'world'), 'event.good', 3, array()),
            'with tags' => array('event.good,region=world:2|c', array(), 'event.good', 2, array('region' => 'world'))
        );
    }

    /**
     * Ensure our rate sampling logic gets executed during tests at least once for
     * a rate different than '1'
     */
    public function testSamplingRateApplies()
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->never())->method('send');
        $client = new Client(array('connection' => $socketMock));

        // There is a really small chance this test will give a false positive.
        // Sorry for that.
        $tags = array();
        $rate = 0.0000000000000001;

        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->incr('event.ok', 2, $rate, $tags)
        );
    }

    public function testCommandMergeTagsIfMergeTagsIsEnabled()
    {
        $expectedRequest = 'event.ok,region=world,severity=low:2|c';
        $defaultTags = array('region' => 'world');
        $tags = array('severity' => 'low');

        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())->method('send')->with($expectedRequest);
        $client = new Client(
            array(
                'connection' => $socketMock,
                'default_tags' => $defaultTags,
                'merge_tags' => true,
            )
        );

        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->incr('event.ok', 2, 1, $tags)
        );
    }

    public function testCommandWontMergeTagsIfMergeTagsIsDisabled()
    {
        $expectedRequest = 'event.ok,severity=low:2|c';
        $defaultTags = array('region' => 'world');
        $tags = array('severity' => 'low');

        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())->method('send')->with($expectedRequest);
        $client = new Client(
            array(
                'connection' => $socketMock,
                'default_tags' => $defaultTags,
                'merge_tags' => false
            )
        );

        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->incr('event.ok', 2, 1, $tags)
        );
    }

    /**
     * @dataProvider provideParamsForDecrAndExpectedRequest
     */
    public function testClientSupportsDecrByDefault(
        $expectedRequest,
        array $defaultTags,
        $stat,
        $count,
        array $tags
    )
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())->method('send')->with($expectedRequest);

        $client = new Client(array('connection' => $socketMock, 'default_tags' => $defaultTags));
        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->decr($stat, $count, 1, $tags)
        );
    }

    public static function provideParamsForDecrAndExpectedRequest()
    {
        return array(
            'no tags' => array('event:-1|c', array(), 'event', 1, array()),
            'with default tags' => array('event.bad,region=world:-2|c', array('region' => 'world'), 'event.bad', 2, array()),
            'with tags' => array('event.bad,region=world:-3|c', array(), 'event.bad', 3, array('region' => 'world')),
            'with tags and default tags' => array(
                'event.bad,region=world,severity=low:-3|c',
                array('region' => 'world'),
                'event.bad',
                3,
                array('severity'=>'low')
            ),
        );
    }

    /**
     * @dataProvider provideParamsForTimingAndExpectedRequest
     */
    public function testClientSupportsTimingByDefault(
        $expectedRequest,
        array $defaultTags,
        $stat,
        $delta,
        array $tags
    )
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())->method('send')->with($expectedRequest);

        $client = new Client(array('connection' => $socketMock, 'default_tags' => $defaultTags));
        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->timing($stat, $delta, 1, $tags)
        );
    }

    public static function provideParamsForTimingAndExpectedRequest()
    {
        return array(
            'no tags' => array('query:1|ms', array(), 'query', 1, array()),
            'with default tags' => array('db.query,region=world:2|ms', array('region' => 'world'), 'db.query', 2, array()),
            'with tags' => array('db.query,region=world:34|ms', array(), 'db.query', 34, array('region' => 'world'))
        );
    }

    /**
     * @dataProvider provideParamsForSetAndExpectedRequest
     */
    public function testClientSupportsSetByDefault(
        $expectedRequest,
        array $defaultTags,
        $stat,
        $value,
        array $tags
    )
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())->method('send')->with($expectedRequest);

        $client = new Client(array('connection' => $socketMock, 'default_tags' => $defaultTags));
        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->set($stat, $value, 1, $tags)
        );
    }

    public static function provideParamsForSetAndExpectedRequest()
    {
        return array(
            'no tags' => array('id:1000|s', array(), 'id', 1000, array()),
            'with default tags' => array('usr.id,region=world:2200|s', array('region' => 'world'), 'usr.id', 2200, array()),
            'with tags' => array('usr.id,region=world:3|s', array(), 'usr.id', 3, array('region' => 'world'))
        );
    }

    /**
     * @dataProvider provideParamsForGaugeAndExpectedRequest
     */
    public function testClientSupportsGaugeByDefault(
        $expectedRequest,
        array $defaultTags,
        $stat,
        $value,
        array $tags
    )
    {
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())->method('send')->with($expectedRequest);

        $client = new Client(array('connection' => $socketMock, 'default_tags' => $defaultTags));
        $this->assertInstanceOf(
            '\\Statsd\\Telegraf\\Client',
            $client->gauge($stat, $value, 1, false, $tags)
        );
    }

    public static function provideParamsForGaugeAndExpectedRequest()
    {
        return array(
            'no tags' => array('cpu_percent:44|g', array(), 'cpu_percent', 44, array()),
            'with default tags' => array('resource.mem_mb,region=world:123|g', array('region' => 'world'), 'resource.mem_mb', 123, array()),
            'with tags' => array('mem_mb,region=world:34|g', array(), 'mem_mb', 34, array('region' => 'world'))
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

    public function testCreateStopWatchUsesClientToSendMetrics()
    {
        $now = microtime(true);
        $socketMock = $this->mockSocketConnection();
        $socketMock->expects($this->once())
            ->method('send')
            ->with($this->matchesRegularExpression('/^foo.bar:\d+\|ms$/'));

        $statsd = new Client(array('connection' => $socketMock));
        $stopWatch = $statsd->createStopWatch($now);
        $this->assertInstanceOf('\\Statsd\\Telegraf\\Client\\StopWatch', $stopWatch);
        $this->assertEquals($now, $stopWatch->getReferenceTimestamp());
        $this->assertSame($statsd, $stopWatch->getClient());

        $stopWatch->send('foo.bar');
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
