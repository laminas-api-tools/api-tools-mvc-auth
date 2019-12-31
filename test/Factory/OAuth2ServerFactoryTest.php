<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\OAuth2ServerFactory;
use PHPUnit_Framework_TestCase as TestCase;

class OAuth2ServerFactoryTest extends TestCase
{
    public function testRaisesExceptionIfAdapterIsMissing()
    {
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'storage adapter');
        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'dsn' => 'sqlite::memory:',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testRaisesExceptionCreatingPdoBackedServerIfDsnIsMissing()
    {
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'Missing DSN');
        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'pdo',
            'username' => 'username',
            'password' => 'password',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testCanCreatePdoAdapterBackedServer()
    {
        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'pdo',
            'dsn' => 'sqlite::memory:',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testCanCreateMongoBackedServerUsingMongoFromServices()
    {
        if (! class_exists('MongoDB')) {
            $this->markTestSkipped('Mongo extension is required for this test');
        }

        $mongoClient = $this->getMockBuilder('MongoDB')
            ->disableOriginalConstructor(true)
            ->getMock();
        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
        $services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('MongoService'))
            ->will($this->returnValue(true));
        $services->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('MongoService'))
            ->will($this->returnValue($mongoClient));

        $config = array(
            'adapter' => 'mongo',
            'locator_name' => 'MongoService',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testRaisesExceptionCreatingMongoBackedServerIfDatabaseIsMissing()
    {
        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'mongo',
        );

        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'database');
        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testCanCreateMongoAdapterBackedServer()
    {
        if (! class_exists('MongoDB')) {
            $this->markTestSkipped('Mongo extension is required for this test');
        }

        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
        $config = array(
            'adapter' => 'mongo',
            'database' => 'api-tools-mvc-auth-test',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }
}
