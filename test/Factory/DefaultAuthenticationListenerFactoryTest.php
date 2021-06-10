<?php

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\ApacheResolver;
use Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter;
use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ApiTools\MvcAuth\Factory;
use Laminas\ApiTools\MvcAuth\FileResolver;
use Laminas\Authentication\Adapter\Http as HttpBasic;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use OAuth2\Server as OAuth2Server;
use OAuth2\Storage\Pdo as PdoStorage;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

use function array_shift;

class DefaultAuthenticationListenerFactoryTest extends TestCase
{
    /** @var Factory\DefaultAuthenticationListenerFactory */
    private $factory;

    /** @var ServiceManager */
    private $services;

    public function setUp(): void
    {
        $this->services = new ServiceManager();
        $this->services->setFactory(AuthHttpAdapter::class, Factory\DefaultAuthHttpAdapterFactory::class);
        $this->services->setFactory(ApacheResolver::class, Factory\ApacheResolverFactory::class);
        $this->services->setFactory(FileResolver::class, Factory\FileResolverFactory::class);
        $this->factory = new Factory\DefaultAuthenticationListenerFactory();
    }

    public function testCreatingOAuth2ServerFromStorageService()
    {
        $adapter = $this->getMockBuilder(PdoStorage::class)->disableOriginalConstructor()->getMock();

        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', [
            'api-tools-oauth2' => [
                'storage'                    => 'TestAdapter',
                'grant_types'                => [
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ],
                'api_problem_error_response' => true,
            ],
        ]);

        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $httpAdapter = $this->getHttpAdapter($listener);

        $this->assertNotInstanceOf(HttpBasic::class, $httpAdapter);
    }

    public function testCallingFactoryWithNoConfigServiceReturnsListenerWithNoHttpAdapter()
    {
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $httpAdapter = $this->getHttpAdapter($listener);

        $this->assertNotInstanceOf(HttpBasic::class, $httpAdapter);
    }

    public function testCallingFactoryWithConfigMissingMvcAuthSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', []);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $httpAdapter = $this->getHttpAdapter($listener);

        $this->assertNotInstanceOf(HttpBasic::class, $httpAdapter);
    }

    public function testCallingFactoryWithConfigMissingAuthenticationSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', ['api-tools-mvc-auth' => []]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $httpAdapter = $this->getHttpAdapter($listener);

        $this->assertNotInstanceOf(HttpBasic::class, $httpAdapter);
    }

    public function testCallingFactoryWithConfigMissingHttpSubSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', ['api-tools-mvc-auth' => ['authentication' => []]]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $httpAdapter = $this->getHttpAdapter($listener);

        $this->assertNotInstanceOf(HttpBasic::class, $httpAdapter);
    }

    public function testCallingFactoryWithConfigMissingAcceptSchemesRaisesException()
    {
        $this->services->setService(
            'config',
            [
                'api-tools-mvc-auth' => [
                    'authentication' => [
                        'http' => [],
                    ],
                ],
            ]
        );

        $factory = $this->factory;

        $this->expectException(ServiceNotCreatedException::class);

        $factory($this->services, 'DefaultAuthenticationListener');
    }

    public function testCallingFactoryWithBasicSchemeButMissingHtpasswdValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', [
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['basic'],
                        'realm'          => 'test',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $httpAdapter = $this->getHttpAdapter($listener);

        $this->assertNotInstanceOf(HttpBasic::class, $httpAdapter);
    }

    public function testCallingFactoryWithDigestSchemeButMissingHtdigestValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', [
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['digest'],
                        'realm'          => 'test',
                        'digest_domains' => '/',
                        'nonce_timeout'  => 3600,
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $httpAdapter = $this->getHttpAdapter($listener);

        $this->assertNotInstanceOf(HttpBasic::class, $httpAdapter);
    }

    public function testCallingFactoryWithBasicSchemeAndHtpasswdValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMockBuilder(AuthenticationServiceInterface::class)->getMock();
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', [
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['basic'],
                        'realm'          => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout'  => 3600,
                        'htpasswd'       => __DIR__ . '/../TestAsset/htpasswd',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);
        $this->assertContains('basic', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithDigestSchemeAndHtdigestValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMockBuilder(AuthenticationServiceInterface::class)->getMock();
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', [
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'http' => [
                        'accept_schemes' => ['digest'],
                        'realm'          => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout'  => 3600,
                        'htdigest'       => __DIR__ . '/../TestAsset/htdigest',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);
        $this->assertContains('digest', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithCustomAuthenticationTypesReturnsListenerComposingThem()
    {
        $authenticationService = $this->getMockBuilder(AuthenticationServiceInterface::class)->getMock();
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', [
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'http'  => [
                        'accept_schemes' => ['digest'],
                        'realm'          => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout'  => 3600,
                        'htdigest'       => __DIR__ . '/../TestAsset/htdigest',
                    ],
                    'types' => [
                        'token',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);
        $this->assertEquals(['digest', 'token'], $listener->getAuthenticationTypes());
    }

    public function testFactoryWillUsePreconfiguredOAuth2ServerInstanceProvidedByLaminasOAuth2()
    {
        // Configure mock OAuth2 Server
        $oauth2Server = $this->getMockBuilder(OAuth2Server::class)->disableOriginalConstructor()->getMock();
        // Wrap it in a factory
        $this->services->setService('Laminas\ApiTools\OAuth2\Service\OAuth2Server', function () use ($oauth2Server) {
            return $oauth2Server;
        });

        // Configure mock OAuth2 Server storage adapter
        $adapter = $this->getMockBuilder(PdoStorage::class)->disableOriginalConstructor()->getMock();

        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', [
            'api-tools-oauth2' => [
                'storage' => 'TestAdapter',
            ],
        ]);

        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $r = new ReflectionProperty($listener, 'adapters');
        $r->setAccessible(true);
        $adapters = $r->getValue($listener);
        $adapter  = array_shift($adapters);
        $this->assertInstanceOf(OAuth2Adapter::class, $adapter);

        $oauth2ServerProperty = new ReflectionProperty($adapter, 'oauth2Server');
        $oauth2ServerProperty->setAccessible(true);
        $actualOauth2Server = $oauth2ServerProperty->getValue($adapter);

        $this->assertSame($oauth2Server, $actualOauth2Server);
    }

    public function testCallingFactoryWithAuthenticationMapReturnsListenerComposingMap()
    {
        $authenticationService = $this->getMockBuilder(AuthenticationServiceInterface::class)->getMock();
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', [
            'api-tools-mvc-auth' => [
                'authentication' => [
                    'map' => [
                        'Testing\V1' => 'oauth2',
                    ],
                ],
            ],
        ]);
        $factory = $this->factory;

        $listener = $factory($this->services, 'DefaultAuthenticationListener');
        $this->assertInstanceOf(DefaultAuthenticationListener::class, $listener);

        $authMapProperty = new ReflectionProperty($listener, 'authMap');
        $authMapProperty->setAccessible(true);
        $actualAuthMap = $authMapProperty->getValue($listener);

        $this->assertSame(['Testing\V1' => 'oauth2'], $actualAuthMap);
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    private function getHttpAdapter(DefaultAuthenticationListener $listener)
    {
        $httpAdapterProperty = new ReflectionProperty($listener, 'httpAdapter');
        $httpAdapterProperty->setAccessible(true);

        return $httpAdapterProperty->getValue($listener);
    }
}
