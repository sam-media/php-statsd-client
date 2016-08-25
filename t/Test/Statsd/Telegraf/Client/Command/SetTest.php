<?php
namespace Test\Statsd\Telegraf\Client\Command;

use Statsd\Telegraf\Client\Command\Set;


class SetTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsCommandInterface()
    {
        $impl = new Set();
        $this->assertInstanceOf('\\Statsd\\Client\\CommandInterface', $impl);
    }

    public function testDefaultTagsIsEmptyByDefault()
    {
        $impl = new Set();
        $this->assertEquals(
            array(),
            $impl->getDefaultTags()
        );
    }

    public function testSetAndGetDefaultTags()
    {
        $tags = array('name1' => 'value1', 'name2' => 'value2');
        $impl = new Set();
        $this->assertEquals(
            $tags,
            $impl->setDefaultTags($tags)->getDefaultTags()
        );
    }

    public function testGetCommandsReturnsArrayOfMethodNames()
    {
        $impl = new Set();
        $commands = $impl->getCommands();
        $this->assertInternalType('array', $commands);
        $this->assertNotEmpty($commands);
        foreach ($commands as $command)
        {
            $this->assertInternalType('callable', array($impl, $command));
        }
    }

    /**
     * @dataProvider provideParametersAndExpectedResultForSet
     */
    public function testSetCreatesProperOutputWhenSampleRateMatches($expected, $stat, $value, $rate, array $tags)
    {
        $impl = new Set();
        $this->assertEquals($expected, $impl->set($stat, $value, $rate, $tags));
    }

    /**
     * @return array
     */
    public static function provideParametersAndExpectedResultForSet()
    {
        return array(
            'set ip no tags' => array('user.ip:127.0.0.1|s', 'user.ip', '127.0.0.1', 1, array()),
            'set username, 1 tag' => array('username,region=world:tester|s', 'username', 'tester', 1, array('region' => 'world')),
            'set instances, multiple tags' => array(
                'instance,region=world,severity=low:specific|s',
                'instance',
                'specific',
                1,
                array('region' => 'world', 'severity' => 'low')
            ),
        );
    }

    public function testSetUsesDefaultTagsIfNoTagsIsSpecified()
    {
        $impl = new Set();
        $impl->setDefaultTags(array('tag1' => 'val1', 'tag2' => 'val2'));
        $this->assertEquals('foo.bar,tag1=val1,tag2=val2:unique|s', $impl->set('foo.bar', 'unique', 1));
    }

    public function testSetIncludesSampleRateInResult()
    {
        $implMock = $this->mockSet(array('genRand'));
        $implMock->expects($this->once())
                ->method('genRand')
                ->will($this->returnValue(0.45)
        );

        $this->assertEquals(
            'foo.bar:unique|s|@0.6',
            $implMock->set('foo.bar', 'unique', 0.6)
        );
    }

    public function testSetReturnsNullWhenSampleRateIsLow()
    {
        $implMock = $this->mockSet(array('genRand'));
        $implMock->expects($this->once())
            ->method('genRand')
            ->will($this->returnValue(0.85)
        );
        $this->assertNull($implMock->set('foo.bar', 'unique', 0.5));
    }

    private function mockSet(array $methods=array())
    {
        $implMock = $this->getMock(
            '\\Statsd\\Telegraf\\Client\\Command\\Set',
            $methods
        );
        return $implMock;
    }
}
