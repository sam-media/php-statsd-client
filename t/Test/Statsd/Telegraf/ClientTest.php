<?php
namespace Test\Statsd\Telegraf;

use StdClass;
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
    public static function provideExpectedSettingsAndConstructorParams()
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
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Call to undefined method Statsd\Telegraf\Client::fooFunc()
     */
    public function testClientWithWrongCommand()
    {
        $client = new Client();
        $client->fooFunc("foo", "bar");
    }
}
