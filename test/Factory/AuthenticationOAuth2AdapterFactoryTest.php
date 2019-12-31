<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\AuthenticationOAuth2AdapterFactory;
use PHPUnit_Framework_TestCase as TestCase;

class AuthenticationOAuth2AdapterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->getMockBuilder('Laminas\ServiceManager\ServiceLocatorInterface')->getMock();
    }


    public function invalidConfiguration()
    {
        return [
            'empty'  => [[]],
            'null'   => [['storage' => null]],
            'bool'   => [['storage' => true]],
            'int'    => [['storage' => 1]],
            'float'  => [['storage' => 1.1]],
            'string' => [['storage' => 'options']],
            'object' => [['storage' => (object) ['storage']]],
        ];
    }

    /**
     * @dataProvider invalidConfiguration
     */
    public function testRaisesExceptionForMissingOrInvalidStorage(array $config)
    {
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'Missing storage');
        AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
    }

    public function testCreatesInstanceFromValidConfiguration()
    {
        $config = [
            'adapter' => 'pdo',
            'storage' => [
                'adapter' => 'pdo',
                'dsn' => 'sqlite::memory:',
            ],
        ];

        $this->services->expects($this->any())
            ->method('get')
            ->with($this->stringContains('Config'))
            ->will($this->returnValue([
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
            ]));

        $adapter = AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter', $adapter);
        $this->assertEquals(['foo'], $adapter->provides());
    }
}
