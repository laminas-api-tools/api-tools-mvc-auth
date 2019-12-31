<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Factory\AuthenticationAdapterDelegatorFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class AuthenticationAdapterDelegatorFactoryTest extends TestCase
{
    /**
     * @var AuthenticationAdapterDelegatorFactory
     */
    private $factory;
    /**
     * @var ServiceManager
     */
    private $services;

    public function setUp()
    {
        // Actual service manager instance, as multiple services may be
        // requested; simplifies testing.
        $this->services = new ServiceManager();
        $this->factory  = new AuthenticationAdapterDelegatorFactory();
        $this->listener = $listener = new DefaultAuthenticationListener();
        $this->callback = function () use ($listener) {
            return $listener;
        };
    }

    public function testReturnsListenerWithNoAdaptersWhenNoAdaptersAreInConfiguration()
    {
        $config = [];
        $this->services->setService('config', $config);

        $factory = $this->factory;

        $listener = $factory(
            $this->services,
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener',
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals([], $listener->getAuthenticationTypes());
    }


    public function testReturnsListenerWithConfiguredAdapters()
    {
        $config = [
            // ensure top-level api-tools-oauth2 are available
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
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'adapters' => [
                        'foo' => [
                            'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter',
                            'options' => [
                                'accept_schemes' => ['basic'],
                                'realm' => 'api',
                                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
                            ],
                        ],
                        'bar' => [
                            'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => [
                                'adapter' => 'pdo',
                                'dsn' => 'sqlite::memory:',
                            ],
                        ],
                        'baz' => [
                            'adapter' => 'UNKNOWN',
                        ],
                        'bat' => [
                            // intentionally empty
                        ],
                    ],
                ],
            ],
        ];
        $this->services->setService('config', $config);
        $this->services->setService(
            'authentication',
            $this->getMockBuilder('Laminas\Authentication\AuthenticationService')->getMock()
        );

        $factory = $this->factory;

        $listener = $factory(
            $this->services,
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener',
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals([
            'foo-basic',
            'bar'
        ], $listener->getAuthenticationTypes());
    }
}
