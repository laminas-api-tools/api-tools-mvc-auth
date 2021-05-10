<?php

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\HttpAdapterFactory;
use Laminas\Authentication\Adapter\Http\ApacheResolver;
use Laminas\Authentication\Adapter\Http as HttpBasic;
use Laminas\Authentication\Adapter\Http\FileResolver;
use Laminas\Authentication\Adapter\Http\ResolverInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;

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
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('accept_schemes');
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
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('accept_schemes');
        HttpAdapterFactory::factory(['accept_schemes' => $acceptSchemes]);
    }

    public function testFactoryRaisesExceptionWhenRealmIsMissing()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('realm');
        HttpAdapterFactory::factory([
            'accept_schemes' => ['basic'],
        ]);
    }

    public function testRaisesExceptionWhenDigestConfiguredAndNoDomainsPresent()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('digest_domains');
        HttpAdapterFactory::factory([
            'accept_schemes' => ['digest'],
            'realm' => 'api',
            'nonce_timeout' => 3600,
        ]);
    }

    public function testRaisesExceptionWhenDigestConfiguredAndNoNoncePresent()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('digest_domains');
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
        $this->assertInstanceOf(HttpBasic::class, $adapter);
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

        $this->assertInstanceOf(HttpBasic::class, $adapter);
        $this->assertInstanceOf(ApacheResolver::class, $adapter->getBasicResolver());
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

        $this->assertInstanceOf(HttpBasic::class, $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertInstanceOf(FileResolver::class, $adapter->getDigestResolver());
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

        $this->assertInstanceOf(HttpBasic::class, $adapter);
        $this->assertInstanceOf(ApacheResolver::class, $adapter->getBasicResolver());
        $this->assertInstanceOf(FileResolver::class, $adapter->getDigestResolver());
    }

    public function testCanReturnBasicAdapterWithCustomResolverFromServiceManager()
    {
        $keyForServiceManager = 'keyForServiceManager';

        $serviceManager = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
        $serviceManager
            ->expects($this->once())
            ->method('has')
            ->with($keyForServiceManager)
            ->will($this->returnValue(true));

        $resolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
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

        $this->assertInstanceOf(HttpBasic::class, $adapter);
        $this->assertSame($resolver, $adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnDigestAdapterWithCustomResolverFromServiceManager()
    {
        $keyForServiceManager = 'keyForServiceManager';

        $serviceManager = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
        $serviceManager
            ->expects($this->once())
            ->method('has')
            ->with($keyForServiceManager)
            ->will($this->returnValue(true));

        $resolver = $this->getMockBuilder(ResolverInterface::class)->getMock();
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

        $this->assertInstanceOf(HttpBasic::class, $adapter);
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

        $this->assertInstanceOf(HttpBasic::class, $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnAdapterWithNoResolversAndInvalidResolverKeys()
    {
        $serviceManager = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
        $serviceManager->expects($this->never())->method('has');

        $adapter = HttpAdapterFactory::factory([
            'accept_schemes' => ['basic', 'digest'],
            'realm' => 'api',
            'digest_domains' => 'https://example.com',
            'nonce_timeout' => 3600,
            'basic_resolver_factory' => null,
            'digest_resolver_factory' => [],
        ], $serviceManager);

        $this->assertInstanceOf(HttpBasic::class, $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }

    public function testCanReturnAdapterWithNoResolversAndMissingServiceManagerEntries()
    {
        $missingKeyForServiceManager = 'missingKeyForServiceManager';

        $serviceManager = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
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

        $this->assertInstanceOf(HttpBasic::class, $adapter);
        $this->assertNull($adapter->getBasicResolver());
        $this->assertNull($adapter->getDigestResolver());
    }
}
