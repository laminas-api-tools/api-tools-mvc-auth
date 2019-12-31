<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\NamedOAuth2ServerFactory;
use Laminas\ApiTools\MvcAuth\Factory\OAuth2ServerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class NamedOAuth2ServerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->setUpConfig(new ServiceManager());
        $this->factory  = new NamedOAuth2ServerFactory();
    }

    public function setUpConfig($services)
    {
        $services->setService('config', [
            'api-tools-oauth2' => [
                'storage' => 'LaminasTest\ApiTools\OAuth2\TestAsset\MockAdapter',
                'grant_types' => [
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ],
                'api_problem_error_response' => true,
            ],
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'adapters' => [
                        'test' => [
                            'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => [
                                'storage' => 'LaminasTest\ApiTools\OAuth2\TestAsset\MockAdapter',
                                'route'   => 'test',
                            ],
                        ],
                        'test2' => [
                            'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => [
                                'storage' => 'LaminasTest\ApiTools\OAuth2\TestAsset\MockAdapter',
                                'route'   => 'test2',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $oauth2StorageAdapter = $this->getMockBuilder('OAuth2\Storage\Memory')
            ->disableOriginalConstructor(true)
            ->getMock();

        $services->setService(
            'LaminasTest\ApiTools\OAuth2\TestAsset\MockAdapter',
            $oauth2StorageAdapter
        );
        return $services;
    }

    public function testCallingReturnedFactoryMultipleTimesWithNoArgumentReturnsSameServerInstance()
    {
        $factory = $this->factory->__invoke($this->services, 'NamedOAuth2Server');
        $server  = $factory();
        $this->assertSame($server, $factory());
    }

    public function testCallingReturnedFactoryMultipleTimesWithSameArgumentReturnsSameServerInstance()
    {
        $factory = $this->factory->__invoke($this->services, 'NamedOAuth2Server');
        $server  = $factory('test');
        $this->assertSame($server, $factory('test'));
    }

    public function testCallingReturnedFactoryMultipleTimesWithDifferentArgumentsReturnsDifferentInstances()
    {
        $factory = $this->factory->__invoke($this->services, 'NamedOAuth2Server');
        $server  = $factory('test');
        $this->assertNotSame($server, $factory());
        $this->assertNotSame($server, $factory('test2'));
    }

    public function testCallingReturnedFactoryWithUnrecognizedArgumentReturnsApplicationWideInstance()
    {
        $factory = $this->factory->__invoke($this->services, 'NamedOAuth2Server');
        $server  = $factory();
        $this->assertSame($server, $factory('unknown'));
    }
}
