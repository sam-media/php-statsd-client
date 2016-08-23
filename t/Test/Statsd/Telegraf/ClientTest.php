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
        $this->assertEquals(array(), $allSettings['tags']);
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
            'tags' => array(),
            'connection' => null,
            'prefix' => ''
        );

        $prefixSettings = $defaultSettings;
        $prefixSettings['prefix'] = 'test';
        $prefixParams = array('prefix' => 'test');

        $tagsAndExceptionsSettings = $defaultSettings;
        $tagsAndExceptionsSettings['tags'] = array('region' => 'world');
        $tagsAndExceptionsSettings['throw_exception'] = true;
        $tagsAndExceptionsParams = array('tags' => array('region' => 'world'), 'throw_exception' => true);

        return array(
            'empty params' => array($defaultSettings, array()),
            'prefix params' => array($prefixSettings, $prefixParams),
            'tags and throw_exception params' => array($tagsAndExceptionsSettings, $tagsAndExceptionsParams),
        );
    }
}
