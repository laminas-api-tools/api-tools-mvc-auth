<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Factory\DefaultAuthenticationListenerFactory;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class DefaultAuthenticationListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory  = new DefaultAuthenticationListenerFactory();
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
        $this->services->setService('config', array('api-tools-mvc-auth' => array('authentication' => array('http' => array()))));
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException', 'accept_schemes');
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
        $this->assertAttributeInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }

    public function testCallingFactoryWithDigestSchemeAndHtdigestValueReturnsListenerWithHttpAdapter()
    {
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
        $this->assertAttributeInstanceOf('Laminas\Authentication\Adapter\Http', 'httpAdapter', $listener);
    }
}
