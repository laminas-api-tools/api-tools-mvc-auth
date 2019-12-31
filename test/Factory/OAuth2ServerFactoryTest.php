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
use ReflectionProperty;

class OAuth2ServerFactoryTest extends TestCase
{
    protected function getOAuth2Options()
    {
        return [
            'api-tools-oauth2' => [
                'grant_types' => [
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ],
                'api_problem_error_response' => true,
            ],
        ];
    }

    protected function mockConfig($services)
    {
        $services->setService('config', $this->getOAuth2Options());
        return $services;
    }

    public function testRaisesExceptionIfAdapterIsMissing()
    {
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'storage adapter');
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'dsn' => 'sqlite::memory:',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testRaisesExceptionCreatingPdoBackedServerIfDsnIsMissing()
    {
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'Missing DSN');
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'pdo',
            'username' => 'username',
            'password' => 'password',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testCanCreatePdoAdapterBackedServer()
    {
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'pdo',
            'dsn' => 'sqlite::memory:',
        ];
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

        $config = [
            'adapter' => 'mongo',
            'locator_name' => 'MongoService',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function testRaisesExceptionCreatingMongoBackedServerIfDatabaseIsMissing()
    {
        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'mongo',
        ];

        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'database');
        $server = OAuth2ServerFactory::factory($config, $services);
    }

    public function testCanCreateMongoAdapterBackedServer()
    {
        if (! class_exists('MongoDB')) {
            $this->markTestSkipped('Mongo extension is required for this test');
        }

        $services = $this->mockConfig(new ServiceManager());
        $config = [
            'adapter' => 'mongo',
            'database' => 'api-tools-mvc-auth-test',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);
    }

    public function disableGrantType()
    {
        return [
            'client_credentials' => ['client_credentials'],
            'authorization_code' => ['authorization_code'],
            'password'           => ['password'],
            'refresh_token'      => ['refresh_token'],
            'jwt'                => ['jwt'],
        ];
    }

    /**
     * @dataProvider disableGrantType
     * @group 77
     */
    public function testServerCreatedHasDefaultGrantTypesAsDefinedByOAuth2Module($disable)
    {
        $options  = $this->getOAuth2Options();
        $options['api-tools-oauth2']['grant_types'][$disable] = false;
        $options['api-tools-oauth2']['storage_settings'] = [
            'client_table'        => 'CLIENTS',
            'code_table'          => 'AUTHORIZATION_CODES',
            'user_table'          => 'USERS',
            'refresh_token_table' => 'REFRESH_TOKENS',
            'jwt_table'           => 'JWT',
        ];

        $services = new ServiceManager();
        $services->setService('config', $options);

        $config = [
            'adapter' => 'pdo',
            'dsn' => 'sqlite::memory:',
        ];
        $server = OAuth2ServerFactory::factory($config, $services);
        $this->assertInstanceOf('OAuth2\Server', $server);

        $grantTypes = $server->getGrantTypes();
        foreach ($options['api-tools-oauth2']['grant_types'] as $type => $enabled) {
            // jwt is hinted differently in OAuth2\Server
            if ($type === 'jwt') {
                $type = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
            }

            // If the grant type is not enabled, it should not be present in
            // the returned grant types.
            if (! $enabled) {
                $this->assertArrayNotHasKey($type, $grantTypes);
                continue;
            }

            // If it *is* enabled, it MUST be present.
            $this->assertArrayHasKey($type, $grantTypes);

            switch ($type) {
                case 'client_credentials':
                    $class = 'OAuth2\GrantType\ClientCredentials';
                    break;
                case 'authorization_code':
                    $class = 'OAuth2\GrantType\AuthorizationCode';
                    break;
                case 'password':
                    $class = 'OAuth2\GrantType\UserCredentials';
                    break;
                case 'urn:ietf:params:oauth:grant-type:jwt-bearer':
                    $class = 'OAuth2\GrantType\JwtBearer';
                    break;
                case 'refresh_token':
                    $class = 'OAuth2\GrantType\RefreshToken';
                    break;
                default:
                    $this->fail(sprintf('Unknown grant type: %s!', $type));
                    break;
            }

            // and have an instance of the appropriate class.
            $this->assertInstanceOf($class, $grantTypes[$type]);
        }

        // Now verify that storage settings are also merged in, which was the
        // original issue.
        $storage = $server->getStorage('scope');
        $r = new ReflectionProperty($storage, 'config');
        $r->setAccessible(true);
        $storageConfig = $r->getValue($storage);
        foreach ($options['api-tools-oauth2']['storage_settings'] as $key => $value) {
            $this->assertArrayHasKey($key, $storageConfig);
            $this->assertEquals($value, $storageConfig[$key]);
        }
    }
}
