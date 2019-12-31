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
        $config = array();
        $this->services->setService('Config', $config);

        $listener = $this->factory->createDelegatorWithName(
            $this->services,
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener',
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener',
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals(array(), $listener->getAuthenticationTypes());
    }


    public function testReturnsListenerWithConfiguredAdapters()
    {
        $config = array(
            // ensure top-level api-tools-oauth2 are available
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
            'api-tools-mvc-auth' => array(
                'authentication' => array(
                    'adapters' => array(
                        'foo' => array(
                            'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter',
                            'options' => array(
                                'accept_schemes' => array('basic'),
                                'realm' => 'api',
                                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
                            ),
                        ),
                        'bar' => array(
                            'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                            'storage' => array(
                                'adapter' => 'pdo',
                                'dsn' => 'sqlite::memory:',
                            ),
                        ),
                        'baz' => array(
                            'adapter' => 'UNKNOWN',
                        ),
                        'bat' => array(
                            // intentionally empty
                        ),
                    ),
                ),
            ),
        );
        $this->services->setService('Config', $config);
        $this->services->setService('authentication', $this->getMock('Laminas\Authentication\AuthenticationService'));

        $listener = $this->factory->createDelegatorWithName(
            $this->services,
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener',
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener',
            $this->callback
        );
        $this->assertSame($this->listener, $listener);
        $this->assertEquals(array(
            'foo-basic',
            'bar'
        ), $listener->getAuthenticationTypes());
    }
}
