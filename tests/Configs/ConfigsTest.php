<?php
/**
 * ConfigsTest.php
 *
 * @author: chazer
 * @created: 27.10.15 17:54
 */

use ISP\Configs\Configs;

class ConfigsTest extends PHPUnit_Framework_TestCase
{
    protected function addParam(Configs $configs, $name, $type, $default = null)
    {
        static $method;
        if (!isset($method)) {
            $reflection_class = new ReflectionClass('\ISP\Configs\Configs');
            $method = $reflection_class->getMethod('addParam');
            $method->setAccessible(true);
        }
        $method->invoke($configs, $name, $type, $default);
    }

    /**
     * @param string $file
     * @param string $format
     * @return Configs
     */
    public function createConfigsObject($file, $format)
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|\ISP\Configs\Configs $c */
        $c = $this->getMockBuilder('\ISP\Configs\Configs')
            ->setMethods(array('initParams'))
            ->setConstructorArgs([$file, $format])
            ->getMock();

        $c->expects($this->any())
            ->method('initParams')
            ->willReturnCallback(function () use ($c) {
                $this->addParam($c, 'Port', Configs::TYPE_INT);
                $this->addParam($c, 'Env', Configs::TYPE_STR_LIST);
                $this->addParam($c, 'Option', Configs::TYPE_STR_LIST);
            });

        return $c;
    }

    public function configFilesProvider()
    {
        $fixturesLocation = __DIR__ . '/fixtures/';
        return [
            [$fixturesLocation, 'test.conf', 'conf']
        ];
    }

    /**
     * @dataProvider configFilesProvider
     */
    public function testLoad($fixPath, $filename, $format)
    {
        /** @var Configs $c */
        $c = $this->createConfigsObject($fixPath . $filename, $format);

        $c->load();

        $this->assertInternalType('string', $c->getParam('Host'));
        $this->assertEquals('localhost', $c->getParam('Host'));

        $this->assertInternalType('int', $c->getParam('Port'));
        $this->assertEquals('9876', $c->getParam('Port'));

        $this->assertInternalType('array', $c->getParam('Env'));
        $this->assertEquals(array(
            0 => 'ONE=1',
            1 => 'TWO=2',
            2 => 'THREE=3',
        ), $c->getParam('Env'));

        $this->assertInternalType('array', $c->getParam('Option'));
    }

    /**
     * @dataProvider configFilesProvider
     */
    public function testSave($fixPath, $filename, $format)
    {
        $compareFile = $fixPath . 'saved.' . $filename;
        $temp = tempnam(sys_get_temp_dir(), $filename);

        try {
            $c = $this->createConfigsObject($temp, $format);

            $c->setParam('Host', 'localhost');
            $c->setParam('Port', 1234);
            $c->setParam('Env', ['ONE=1', 'TWO=2', 'THREE=3']);

            $c->save();

            $this->assertFileEquals($compareFile, $temp);

        } catch (Exception $e) {
            file_exists($temp) && unlink($temp);
        }
    }

    public function testToBoolean()
    {
        $c = $this->createConfigsObject(null, null);

        $this->assertTrue($c->toBoolean(true));
        $this->assertTrue($c->toBoolean(1));
        $this->assertTrue($c->toBoolean(1.0));
        $this->assertTrue($c->toBoolean('1'));
        $this->assertTrue($c->toBoolean('1.0'));
        $this->assertTrue($c->toBoolean('on'));
        $this->assertTrue($c->toBoolean('On'));
        $this->assertTrue($c->toBoolean('y'));
        $this->assertTrue($c->toBoolean('Yes'));

        $this->assertFalse($c->toBoolean(false));
        $this->assertFalse($c->toBoolean(null));
        $this->assertFalse($c->toBoolean([]));
        $this->assertFalse($c->toBoolean(0));
        $this->assertFalse($c->toBoolean(0.0));
        $this->assertFalse($c->toBoolean('0'));
        $this->assertFalse($c->toBoolean('0.0'));
        $this->assertFalse($c->toBoolean('n'));
        $this->assertFalse($c->toBoolean('N'));
        $this->assertFalse($c->toBoolean('No'));
    }
}
