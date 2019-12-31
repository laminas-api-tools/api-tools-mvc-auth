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
    private $htpasswd;
    private $htdigest;

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
        HttpAdapterFactory::factory([]);
    }

    public function invalidAcceptSchemes()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'zero' => [0],
            'int' => [1],
            'zerofloat' => [0.0],
            'float' => [1.1],
            'string' => ['basic'],
            'object' => [(object) ['basic']],
        ];
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
        HttpAdapterFactory::factory(['accept_schemes' => $acceptSchemes]);
    }

    public function testFactoryRaisesExceptionWhenRealmIsMissing()
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'realm'
        );
        HttpAdapterFactory::factory([
            'accept_schemes' => ['basic'],
        ]);
    }

    public function testRaisesExceptionWhenDigestConfiguredAndNoDomainsPresent()
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'digest_domains'
        );
        HttpAdapterFactory::factory([
            'accept_schemes' => ['digest'],
            'realm' => 'api',
            'nonce_timeout' => 3600,
        ]);
    }

    public function testRaisesExceptionWhenDigestConfiguredAndNoNoncePresent()
    {
        $this->setExpectedException(
            'Laminas\ServiceManager\Exception\ServiceNotCreatedException',
            'digest_domains'
        );
        HttpAdapterFactory::factory([
            'accept_schemes' => ['digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
        ]);
    }

    public function validConfigWithoutResolvers()
    {
        return [
            'basic' => [[
                'accept_schemes' => ['basic'],
                'realm' => 'api',
            ]],
            'digest' => [[
                'accept_schemes' => ['digest'],
                'realm' => 'api',
                'digest_domains' => 'https://example.com',
                'nonce_timeout' => 3600,
            ]],
            'both' => [[
                'accept_schemes' => ['basic', 'digest'],
                'realm' => 'api',
                'digest_domains' => 'https://example.com',
                'nonce_timeout' => 3600,
            ]],
        ];
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
        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic'],
            'realm' => 'api',
            'htpasswd' => $this->htpasswd,
        ]);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\ApacheResolver', $adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnDigestAdapterWithFileResolver()
    {
        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'htdigest' => $this->htdigest,
        ]);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\FileResolver', $adapter->getDigestResolver());
    }

    public function testCanReturnCompoundAdapter()
    {
        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic', 'digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'htpasswd' => $this->htpasswd,
            'htdigest' => $this->htdigest,
        ]);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\ApacheResolver', $adapter->getBasicResolver());
        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http\FileResolver', $adapter->getDigestResolver());
    }

    public function testCanReturnBasicAdapterWithCustomResolverFromServiceManager()
    {
        $keyForServiceManager = 'keyForServiceManager';

        $serviceManager = $this->getMock('\Laminas\ServiceManager\ServiceLocatorInterface');
        $serviceManager
            ->expects($this->once())
            ->method('has')
            ->with($keyForServiceManager)
            ->will($this->returnValue(true));

        $resolver = $this->getMock('\Laminas\Authentication\Adapter\Http\ResolverInterface');
        $serviceManager
            ->expects($this->once())
            ->method('get')
            ->with($keyForServiceManager)
            ->will($this->returnValue($resolver));

        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic', 'digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'htpasswd' => $this->htpasswd,
            'basic_resolver_factory' => $keyForServiceManager,
        ], $serviceManager);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertSame($resolver, $adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnDigestAdapterWithCustomResolverFromServiceManager()
    {
        $keyForServiceManager = 'keyForServiceManager';

        $serviceManager = $this->getMock('\Laminas\ServiceManager\ServiceLocatorInterface');
        $serviceManager
            ->expects($this->once())
            ->method('has')
            ->with($keyForServiceManager)
            ->will($this->returnValue(true));

        $resolver = $this->getMock('\Laminas\Authentication\Adapter\Http\ResolverInterface');
        $serviceManager
            ->expects($this->once())
            ->method('get')
            ->with($keyForServiceManager)
            ->will($this->returnValue($resolver));

        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic', 'digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'htdigest' => $this->htdigest,
            'digest_resolver_factory' => $keyForServiceManager,
        ], $serviceManager);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertSame($resolver, $adapter->getDigestResolver());
    }

    public function testCanReturnAdapterWithNoResolversAndInvalidServiceManager()
    {
        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic', 'digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'basic_resolver_factory' => 'uselessKeyDueToMissingServiceManager',
            'digest_resolver_factory' => 'uselessKeyDueToMissingServiceManager',
        ]);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnAdapterWithNoResolversAndInvalidResolverKeys()
    {
        $serviceManager = $this->getMock('\Laminas\ServiceManager\ServiceLocatorInterface');
        $serviceManager->expects($this->never())->method('has');

        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic', 'digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'basic_resolver_factory' => null,
            'digest_resolver_factory' => [],
        ], $serviceManager);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnAdapterWithNoResolversAndMissingServiceManagerEntries()
    {
        $missingKeyForServiceManager = 'missingKeyForServiceManager';

        $serviceManager = $this->getMock('\Laminas\ServiceManager\ServiceLocatorInterface');
        $serviceManager
            ->expects($this->any())
            ->method('has')
            ->with($missingKeyForServiceManager)
            ->will($this->returnValue(false));
        $serviceManager
            ->expects($this->never())
            ->method('get');

        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic', 'digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'basic_resolver_factory' => $missingKeyForServiceManager,
            'digest_resolver_factory' => $missingKeyForServiceManager,
        ], $serviceManager);

        $this->assertInstanceOf('Laminas\Authentication\Adapter\Http', $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }
}
