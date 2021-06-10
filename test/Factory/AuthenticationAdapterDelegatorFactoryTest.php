<?php

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ApiTools\MvcAuth\Factory\AuthenticationAdapterDelegatorFactory;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class AuthenticationAdapterDelegatorFactoryTest extends TestCase
{
    /** @var AuthenticationAdapterDelegatorFactory */
    private $factory;
    /** @var ServiceManager */
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
            DefaultAuthenticationListener::class,
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals([], $listener->getAuthenticationTypes());
    }

    public function testReturnsListenerWithConfiguredAdapters()
    {
        $config = [
            // ensure top-level api-tools-oauth2 are available
            'api-tools-oauth2'   => [
                'grant_types'                => [
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
                            'adapter' => HttpAdapter::class,
                            'options' => [
                                'accept_schemes' => ['basic'],
                                'realm'          => 'api',
                                'htpasswd'       => __DIR__ . '/../TestAsset/htpasswd',
                            ],
                        ],
                        'bar' => [
                            'adapter' => OAuth2Adapter::class,
                            'storage' => [
                                'adapter' => 'pdo',
                                'dsn'     => 'sqlite::memory:',
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
            $this->getMockBuilder(AuthenticationService::class)->getMock()
        );

        $factory = $this->factory;

        $listener = $factory(
            $this->services,
            DefaultAuthenticationListener::class,
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals([
            'foo-basic',
            'bar',
        ], $listener->getAuthenticationTypes());
    }
}
