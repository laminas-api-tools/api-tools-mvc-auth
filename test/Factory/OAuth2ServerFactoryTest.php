<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\OAuth2ServerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class OAuth2ServerFactoryTest extends TestCase
{
    protected function getOAuth2Options()
    {
        return array(
            'api-tools-oauth2' => array(
                'grant_types' => array(
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ),
                'api_problem_error_response' => true,
            ),
        );
    }

    protected function mockConfig($services)
    {
        $services->setService('Config', $this->getOAuth2Options());
        return $services;
    }

    public function testRaisesExceptionIfAdapterIsMissing()
    {
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'storage adapter');
        $services = $this->mockConfig(new ServiceManager());
        $config = array(
            'dsn' => 'sqlite::memory:',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testRaisesExceptionCreatingPdoBackedServerIfDsnIsMissing()
    {
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'Missing DSN');
        $services = $this->mockConfig(new ServiceManager());
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
        $services = $this->mockConfig(new ServiceManager());
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

        $services = $this->mockConfig(new ServiceManager());
        $mongoClient = $this->getMockBuilder('MongoDB')
            ->disableOriginalConstructor(true)
            ->getMock();
        $services->setService('MongoService', $mongoClient);

        $config = array(
            'adapter' => 'mongo',
            'locator_name' => 'MongoService',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testRaisesExceptionCreatingMongoBackedServerIfDatabaseIsMissing()
    {
        $services = $this->mockConfig(new ServiceManager());
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

        $services = $this->mockConfig(new ServiceManager());
        $config = array(
            'adapter' => 'mongo',
            'database' => 'api-tools-mvc-auth-test',
        );
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }
}
