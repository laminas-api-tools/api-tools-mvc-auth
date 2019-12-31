<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Factory\DefaultAuthenticationListenerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;

class DefaultAuthenticationListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory(
            'Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter',
            'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthHttpAdapterFactory'
        );
        $this->services->setFactory(
            'Laminas\ApiTools\MvcAuth\ApacheResolver',
            'Laminas\ApiTools\MvcAuth\Factory\ApacheResolverFactory'
        );
        $this->services->setFactory(
            'Laminas\ApiTools\MvcAuth\FileResolver',
            'Laminas\ApiTools\MvcAuth\Factory\FileResolverFactory'
        );
        $this->factory  = new DefaultAuthenticationListenerFactory();
    }

    public function testCreatingOAuth2ServerFromStorageService()
    {
        $adapter = $this->getMockBuilder('OAuth2\Storage\Pdo')->disableOriginalConstructor()->getMock();

        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', array(
            'api-tools-oauth2' => array(
                'storage' => 'TestAdapter',
                'grant_types' => array(
                    'client_credentials' => true,
                    'authorization_code' => true,
                    'password'           => true,
                    'refresh_token'      => true,
                    'jwt'                => true,
                ),
                'api_problem_error_response' => true,
            )
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithNoConfigServiceReturnsListenerWithNoHttpAdapter()
    {
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingMvcAuthSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array());
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAuthenticationSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array('api-tools-mvc-auth' => array()));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingHttpSubSubSectionReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array('api-tools-mvc-auth' => array('authentication' => array())));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithConfigMissingAcceptSchemesRaisesException()
    {
        $this->services->setService(
            'config',
            array(
                'api-tools-mvc-auth' => array(
                    'authentication' => array(
                        'http' => array(),
                    ),
                ),
            )
        );
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException');
        $listener = $this->factory->createService($this->services);
    }

    public function testCallingFactoryWithBasicSchemeButMissingHtpasswdValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array(
            'api-tools-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'test',
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithDigestSchemeButMissingHtdigestValueReturnsListenerWithNoHttpAdapter()
    {
        $this->services->setService('config', array(
            'api-tools-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'test',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeNotInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithBasicSchemeAndHtpasswdValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMock('Laminas\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'api-tools-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('basic'),
                        'realm' => 'My Web Site',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htpasswd' => __DIR__ . '/../TestAsset/htpasswd'
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertContains('basic', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithDigestSchemeAndHtdigestValueReturnsListenerWithHttpAdapter()
    {
        $authenticationService = $this->getMock('Laminas\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'api-tools-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/../TestAsset/htdigest'
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertContains('digest', $listener->getAuthenticationTypes());
    }

    public function testCallingFactoryWithCustomAuthenticationTypesReturnsListenerComposingThem()
    {
        $authenticationService = $this->getMock('Laminas\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'api-tools-mvc-auth' => array(
                'authentication' => array(
                    'http' => array(
                        'accept_schemes' => array('digest'),
                        'realm' => 'User Area',
                        'digest_domains' => '/',
                        'nonce_timeout' => 3600,
                        'htdigest' => __DIR__ . '/../TestAsset/htdigest'
                    ),
                    'types' => array(
                        'token',
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertEquals(array('digest', 'token'), $listener->getAuthenticationTypes());
    }

    public function testFactoryWillUsePreconfiguredOAuth2ServerInstanceProvidedByLaminasOAuth2()
    {
        // Configure mock OAuth2 Server
        $oauth2Server = $this->getMockBuilder('OAuth2\Server')->disableOriginalConstructor()->getMock();
        // Wrap it in a factory
        $this->services->setService('Laminas\ApiTools\OAuth2\Service\OAuth2Server', function () use ($oauth2Server) {
            return $oauth2Server;
        });
        
        // Configure mock OAuth2 Server storage adapter
        $adapter = $this->getMockBuilder('OAuth2\Storage\Pdo')->disableOriginalConstructor()->getMock();
        $this->services->setService('TestAdapter', $adapter);
        $this->services->setService('config', array(
            'api-tools-oauth2' => array(
                'storage' => 'TestAdapter'
            )
        ));
        
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);

        $r = new ReflectionProperty($listener, 'adapters');
        $r->setAccessible(true);
        $adapters = $r->getValue($listener);
        $adapter = array_shift($adapters);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter', $adapter);
        $this->assertAttributeSame($oauth2Server, 'oauth2Server', $adapter);
    }

    public function testCallingFactoryWithAuthenticationMapReturnsListenerComposingMap()
    {
        $authenticationService = $this->getMock('Laminas\Authentication\AuthenticationServiceInterface');
        $this->services->setService('authentication', $authenticationService);
        $this->services->setService('config', array(
            'api-tools-mvc-auth' => array(
                'authentication' => array(
                    'map' => array(
                        'Testing\V1' => 'oauth2',
                    ),
                ),
            ),
        ));
        $listener = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener', $listener);
        $this->assertAttributeEquals(array('Testing\V1' => 'oauth2'), 'authMap', $listener);
    }
}
