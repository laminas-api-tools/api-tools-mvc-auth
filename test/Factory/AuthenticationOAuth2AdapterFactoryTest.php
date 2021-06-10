<?php

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ApiTools\MvcAuth\Factory\AuthenticationOAuth2AdapterFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;

class AuthenticationOAuth2AdapterFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->services = $this->getMockBuilder(ServiceLocatorInterface::class)->getMock();
    }

    /** @psalm-return array<string, array{0: array<array-key, mixed>}> */
    public function invalidConfiguration(): array
    {
        return [
            'empty'  => [[]],
            'null'   => [['storage' => null]],
            'bool'   => [['storage' => true]],
            'int'    => [['storage' => 1]],
            'float'  => [['storage' => 1.1]],
            'string' => [['storage' => 'options']],
            'object' => [['storage' => (object) ['storage']]],
        ];
    }

    /**
     * @dataProvider invalidConfiguration
     * @psalm-param array<array-key, mixed> $config
     */
    public function testRaisesExceptionForMissingOrInvalidStorage(array $config): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('Missing storage');
        AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
    }

    public function testCreatesInstanceFromValidConfiguration(): void
    {
        $config = [
            'adapter' => 'pdo',
            'storage' => [
                'adapter' => 'pdo',
                'dsn'     => 'sqlite::memory:',
            ],
        ];

        $this->services->expects($this->any())
            ->method('get')
            ->with($this->stringContains('Config'))
            ->will($this->returnValue([
                'api-tools-oauth2' => [
                    'grant_types'                => [
                        'client_credentials' => true,
                        'authorization_code' => true,
                        'password'           => true,
                        'refresh_token'      => true,
                        'jwt'                => true,
                    ],
                    'api_problem_error_response' => true,
                ],
            ]));

        $adapter = AuthenticationOAuth2AdapterFactory::factory('foo', $config, $this->services);
        $this->assertInstanceOf(OAuth2Adapter::class, $adapter);
        $this->assertEquals(['foo'], $adapter->provides());
    }
}
