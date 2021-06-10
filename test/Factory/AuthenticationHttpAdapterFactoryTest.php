<?php

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ApiTools\MvcAuth\Factory\AuthenticationHttpAdapterFactory;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;

class AuthenticationHttpAdapterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
    }

    public function testRaisesExceptionIfNoAuthenticationServicePresent()
    {
        $this->services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue(false));

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('missing AuthenticationService');
        AuthenticationHttpAdapterFactory::factory('foo', [], $this->services);
    }

    /** @psalm-return array<string, array{0: array<array-key, mixed>}> */
    public function invalidConfiguration(): array
    {
        return [
            'empty'  => [[]],
            'null'   => [['options' => null]],
            'bool'   => [['options' => true]],
            'int'    => [['options' => 1]],
            'float'  => [['options' => 1.1]],
            'string' => [['options' => 'options']],
            'object' => [['options' => (object) ['options']]],
        ];
    }

    /**
     * @dataProvider invalidConfiguration
     * @psalm-param array<string, mixed> $config
     */
    public function testRaisesExceptionIfMissingConfigurationOptions(array $config): void
    {
        $this->services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue(true));

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('missing options');
        AuthenticationHttpAdapterFactory::factory('foo', $config, $this->services);
    }

    /**
     * @psalm-return array<string, array{
     *     0: array<string, mixed>
     *     1: string[]
     * }>
     */
    public function validConfiguration(): array
    {
        return [
            'basic'  => [
                [
                    'accept_schemes' => ['basic'],
                    'realm'          => 'api',
                    'htpasswd'       => __DIR__ . '/../TestAsset/htpasswd',
                ],
                ['foo-basic'],
            ],
            'digest' => [
                [
                    'accept_schemes' => ['digest'],
                    'realm'          => 'api',
                    'digest_domains' => 'https://example.com',
                    'nonce_timeout'  => 3600,
                    'htdigest'       => __DIR__ . '/../TestAsset/htdigest',
                ],
                ['foo-digest'],
            ],
            'both'   => [
                [
                    'accept_schemes' => ['basic', 'digest'],
                    'realm'          => 'api',
                    'digest_domains' => 'https://example.com',
                    'nonce_timeout'  => 3600,
                    'htpasswd'       => __DIR__ . '/../TestAsset/htpasswd',
                    'htdigest'       => __DIR__ . '/../TestAsset/htdigest',
                ],
                ['foo-basic', 'foo-digest'],
            ],
        ];
    }

    /**
     * @dataProvider validConfiguration
     * @psalm-param array<string, mixed> $options
     * @psalm-param string[] $provides
     */
    public function testCreatesHttpAdapterWhenConfigurationIsValid(array $options, array $provides): void
    {
        $authService = $this->getMockBuilder(AuthenticationService::class)->getMock();
        $this->services->expects($this->atLeastOnce())
            ->method('has')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue(true));
        $this->services->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('authentication'))
            ->will($this->returnValue($authService));

        $adapter = AuthenticationHttpAdapterFactory::factory('foo', ['options' => $options], $this->services);
        $this->assertInstanceOf(HttpAdapter::class, $adapter);
        $this->assertEquals($provides, $adapter->provides());
    }
}
