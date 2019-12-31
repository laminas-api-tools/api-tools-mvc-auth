<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\HttpAdapterFactory;
use PHPUnit_Framework_TestCase as TestCase;

class HttpAdapterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->htpasswd = __DIR__ . '/../TestAsset/htpasswd';
        $this->htdigest = __DIR__ . '/../TestAsset/htdigest';
    }

    public function testFactoryRaisesExceptionWhenNoAcceptSchemesPresent()
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'accept_schemes'
        );
        HttpAdapterFactory::factory(array());
    }

    public function invalidAcceptSchemes()
    {
        return array(
            'null' => array(null),
            'true' => array(true),
            'false' => array(false),
            'zero' => array(0),
            'int' => array(1),
            'zerofloat' => array(0.0),
            'float' => array(1.1),
            'string' => array('basic'),
            'object' => array((object) array('basic')),
        );
    }

    /**
     * @dataProvider invalidAcceptSchemes
     */
    public function testFactoryRaisesExceptionWhenAcceptSchemesIsNotAnArray($acceptSchemes)
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'accept_schemes'
        );
        HttpAdapterFactory::factory(array('accept_schemes' => $acceptSchemes));
    }

    public function testFactoryRaisesExceptionWhenRealmIsMissing()
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'realm'
        );
        HttpAdapterFactory::factory(array(
            'accept_schemes' => array('basic'),
        ));
    }

    public function testRaisesExceptionWhenDigestConfiguredAndNoDomainsPresent()
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'digest_domains'
        );
        HttpAdapterFactory::factory(array(
            'accept_schemes' => array('digest'),
            'realm' => 'api',
            'nonce_timeout' => 3600,
        ));
    }

    public function testRaisesExceptionWhenDigestConfiguredAndNoNoncePresent()
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'digest_domains'
        );
        HttpAdapterFactory::factory(array(
            'accept_schemes' => array('digest'),
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
        ));
    }

    public function validConfigWithoutResolvers()
    {
        return array(
            'basic' => array(array(
                'accept_schemes' => array('basic'),
                'realm' => 'api',
            )),
            'digest' => array(array(
                'accept_schemes' => array('digest'),
                'realm' => 'api',
                'digest_domains' => 'https://example.com',
                'nonce_timeout' => 3600,
            )),
            'both' => array(array(
                'accept_schemes' => array('basic', 'digest'),
                'realm' => 'api',
                'digest_domains' => 'https://example.com',
                'nonce_timeout' => 3600,
            )),
        );
    }

    /**
     * @dataProvider validConfigWithoutResolvers
     */
    public function testCanReturnAdapterWithNoResolvers($config)
    {
        $adapter = HttpAdapterFactory::factory($config);
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnBasicAdapterWithApacheResolver()
    {
        $adapter = HttpAdapterFactory::factory(array(
            'accept_schemes' => array('basic'),
            'realm' => 'api',
            'htpasswd' => $this->htpasswd,
        ));

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\ApacheResolver', $adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnDigestAdapterWithFileResolver()
    {
        $adapter = HttpAdapterFactory::factory(array(
            'accept_schemes' => array('digest'),
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'htdigest' => $this->htdigest,
        ));

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\FileResolver', $adapter->getDigestResolver());
    }

    public function testCanReturnCompoundAdapter()
    {
        $adapter = HttpAdapterFactory::factory(array(
            'accept_schemes' => array('basic', 'digest'),
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'htpasswd' => $this->htpasswd,
            'htdigest' => $this->htdigest,
        ));

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\ApacheResolver', $adapter->getBasicResolver());
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\FileResolver', $adapter->getDigestResolver());
    }
}
