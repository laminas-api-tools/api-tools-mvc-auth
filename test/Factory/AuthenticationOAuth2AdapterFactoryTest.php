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
        $this->services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
    }


    public function invalidConfiguration()
    {
        return array(
            'empty'  => array(array()),
            'null'   => array(array('storage' => null)),
            'bool'   => array(array('storage' => true)),
            'int'    => array(array('storage' => 1)),
            'float'  => array(array('storage' => 1.1)),
            'string' => array(array('storage' => 'options')),
            'object' => array(array('storage' => (object) array('storage'))),
        );
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
        $config = array(
            'adapter' => 'pdo',
            'storage' => array(
                'adapter' => 'pdo',
                'dsn' => 'sqlite::memory:',
            ),
        );

        $this->services->expects($this->any())
            ->method('get')
            ->with($this->stringContains('Config'))
            ->will($this->returnValue(array(
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
            )));

        $adapter = AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter', $adapter);
        $this->assertEquals(array('foo'), $adapter->provides());
    }
}
