<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\AuthenticationHttpAdapterFactory;
use PHPUnit_Framework_TestCase as TestCase;

class AuthenticationHttpAdapterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
    }

    public function testRaisesExceptionIfNoAuthenticationServicePresent()
    {
        $this->services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue(false));

        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'missing AuthenticationService'
        );
        AuthenticationHttpAdapterFactory::factory('foo', array(), $this->services);
    }

    public function invalidConfiguration()
    {
        return array(
            'empty'  => array(array()),
            'null'   => array(array('options' => null)),
            'bool'   => array(array('options' => true)),
            'int'    => array(array('options' => 1)),
            'float'  => array(array('options' => 1.1)),
            'string' => array(array('options' => 'options')),
            'object' => array(array('options' => (object) array('options'))),
        );
    }

    /**
     * @dataProvider invalidConfiguration
     */
    public function testRaisesExceptionIfMissingConfigurationOptions($config)
    {
        $this->services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue(true));

        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'missing options'
        );
        AuthenticationHttpAdapterFactory::factory('foo', $config, $this->services);
    }

    public function validConfiguration()
    {
        return array(
            'basic' => array(array(
                'accept_schemes' => array('basic'),
                'realm' => 'api',
                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
            ), array('foo-basic')),
            'digest' => array(array(
                'accept_schemes' => array('digest'),
                'realm' => 'api',
                'digest_domains' => 'https://example.com',
                'nonce_timeout' => 3600,
                'htdigest' => __DIR__ . '/../TestAsset/htdigest',
            ), array('foo-digest')),
            'both' => array(array(
                'accept_schemes' => array('basic', 'digest'),
                'realm' => 'api',
                'digest_domains' => 'https://example.com',
                'nonce_timeout' => 3600,
                'htpasswd' => __DIR__ . '/../TestAsset/htpasswd',
                'htdigest' => __DIR__ . '/../TestAsset/htdigest',
            ), array('foo-basic', 'foo-digest')),
        );
    }

    /**
     * @dataProvider validConfiguration
     */
    public function testCreatesHttpAdapterWhenConfigurationIsValid(array $options, array $provides)
    {
        $authService = $this->getMock('Laminas\Authentication\AuthenticationService');
        $this->services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue(true));
        $this->services->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue($authService));

        $adapter = AuthenticationHttpAdapterFactory::factory('foo', array('options' => $options), $this->services);
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter', $adapter);
        $this->assertEquals($provides, $adapter->provides());
    }
}
