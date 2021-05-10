<?php

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization;
use Laminas\ApiTools\MvcAuth\Factory\AclAuthorizationFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class AclAuthorizationFactoryTest extends TestCase
{
    private $factory;
    private $services;

    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory  = new AclAuthorizationFactory();
    }

    public function whitelistAclProvider()
    {
        $entityConfig = [
            'GET'    => false,
            'POST'   => false,
            'PUT'    => true,
            'PATCH'  => true,
            'DELETE' => true,
        ];
        $collectionConfig = [
            'GET'    => false,
            'POST'   => true,
            'PUT'    => false,
            'PATCH'  => false,
            'DELETE' => false,
        ];
        $rpcConfig = [
            'GET'    => false,
            'POST'   => true,
            'PUT'    => false,
            'PATCH'  => false,
            'DELETE' => false,
        ];

        foreach (['Foo\Bar\\', 'Foo-Bar-'] as $namespace) {
            yield $namespace => [['api-tools-mvc-auth' => ['authorization' => [
                $namespace . 'RestController' => [
                    'entity' => $entityConfig,
                    'collection' => $collectionConfig,
                ],
                $namespace . 'RpcController' => [
                    'actions' => [
                        'do' => $rpcConfig,
                    ],
                ],
            ]]]];
        }
    }

    /**
     * @dataProvider whitelistAclProvider
     */
    public function testCanCreateWhitelistAcl(array $config)
    {
        $this->services->setService('config', $config);

        $factory = $this->factory;

        $acl = $factory($this->services, 'AclAuthorization');

        $this->assertInstanceOf(AclAuthorization::class, $acl);

        $authorizations = $config['api-tools-mvc-auth']['authorization'];

        foreach ($authorizations as $resource => $rules) {
            // Internally, laminas-mvc is always using namespace separators, so
            // ensure we test against those specifically.
            $resource = strtr($resource, '-', '\\');
            switch (true) {
                case (array_key_exists('entity', $rules)):
                    foreach ($rules['entity'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::entity', $method));
                    }
                    break;
                case (array_key_exists('collection', $rules)):
                    foreach ($rules['collection'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::collection', $method));
                    }
                    break;
                case (array_key_exists('actions', $rules)):
                    foreach ($rules['actions'] as $action => $actionRules) {
                        foreach ($actionRules as $method => $expected) {
                            $assertion = 'assert' . ($expected ? 'False' : 'True');
                            $this->$assertion($acl->isAllowed('guest', $resource . '::' . $action, $method));
                        }
                    }
                    break;
            }
        }
    }

    public function testBlacklistAclSpecificationHonorsBooleansSetForMethods()
    {
        $config = ['api-tools-mvc-auth' => ['authorization' => [
            'deny_by_default' => true,
            'Foo\Bar\RestController' => [
                'entity' => [
                    'GET'    => false,
                    'POST'   => false,
                    'PUT'    => true,
                    'PATCH'  => true,
                    'DELETE' => true,
                ],
                'collection' => [
                    'GET'    => false,
                    'POST'   => true,
                    'PUT'    => false,
                    'PATCH'  => false,
                    'DELETE' => false,
                ],
            ],
            'Foo\Bar\RpcController' => [
                'actions' => [
                    'do' => [
                        'GET'    => false,
                        'POST'   => true,
                        'PUT'    => false,
                        'PATCH'  => false,
                        'DELETE' => false,
                    ],
                ],
            ],
        ]]];
        $this->services->setService('config', $config);

        $factory = $this->factory;

        $acl = $factory($this->services, 'AclAuthorization');

        $this->assertInstanceOf(AclAuthorization::class, $acl);

        $authorizations = $config['api-tools-mvc-auth']['authorization'];
        unset($authorizations['deny_by_default']);

        foreach ($authorizations as $resource => $rules) {
            switch (true) {
                case (array_key_exists('entity', $rules)):
                    foreach ($rules['entity'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::entity', $method));
                    }
                    break;
                case (array_key_exists('collection', $rules)):
                    foreach ($rules['collection'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::collection', $method));
                    }
                    break;
                case (array_key_exists('actions', $rules)):
                    foreach ($rules['actions'] as $action => $actionRules) {
                        foreach ($actionRules as $method => $expected) {
                            $assertion = 'assert' . ($expected ? 'False' : 'True');
                            $this->$assertion($acl->isAllowed('guest', $resource . '::' . $action, $method));
                        }
                    }
                    break;
            }
        }
    }

    public function testBlacklistAclsDenyByDefaultForUnspecifiedHttpMethods()
    {
        $config = ['api-tools-mvc-auth' => ['authorization' => [
            'deny_by_default' => true,
            'Foo\Bar\RestController' => [
                'entity' => [
                    'GET'    => false,
                    'POST'   => false,
                ],
                'collection' => [
                    'GET'    => false,
                    'PUT'    => false,
                    'PATCH'  => false,
                    'DELETE' => false,
                ],
            ],
            'Foo\Bar\RpcController' => [
                'actions' => [
                    'do' => [
                        'GET'    => false,
                        'PUT'    => false,
                        'PATCH'  => false,
                        'DELETE' => false,
                    ],
                ],
            ],
        ]]];
        $this->services->setService('config', $config);

        $factory = $this->factory;

        $acl = $factory($this->services, 'AclAuthorization');

        $this->assertInstanceOf(AclAuthorization::class, $acl);

        $authorizations = $config['api-tools-mvc-auth']['authorization'];
        unset($authorizations['deny_by_default']);

        $this->assertFalse($acl->isAllowed('guest', 'Foo\Bar\RestController::entity', 'PATCH'));
        $this->assertFalse($acl->isAllowed('guest', 'Foo\Bar\RestController::entity', 'PUT'));
        $this->assertFalse($acl->isAllowed('guest', 'Foo\Bar\RestController::entity', 'DELETE'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RestController::entity', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RestController::entity', 'POST'));

        $this->assertFalse($acl->isAllowed('guest', 'Foo\Bar\RestController::collection', 'POST'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RestController::collection', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RestController::collection', 'PATCH'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RestController::collection', 'PUT'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RestController::collection', 'DELETE'));

        $this->assertFalse($acl->isAllowed('guest', 'Foo\Bar\RpcController::do', 'POST'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RpcController::do', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RpcController::do', 'PUT'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RpcController::do', 'PATCH'));
        $this->assertTrue($acl->isAllowed('guest', 'Foo\Bar\RpcController::do', 'DELETE'));
    }

    public function testRpcActionsAreNormalizedWhenCreatingAcl()
    {
        $config = ['api-tools-mvc-auth' => ['authorization' => [
            'Foo\Bar\RpcController' => [
                'actions' => [
                    'Do' => [
                        'GET'    => false,
                        'POST'   => true,
                        'PUT'    => false,
                        'PATCH'  => false,
                        'DELETE' => false,
                    ],
                ],
            ],
        ]]];
        $this->services->setService('config', $config);

        $factory = $this->factory;

        $acl = $factory($this->services, 'AclAuthorization');
        $this->assertInstanceOf(AclAuthorization::class, $acl);
        $this->assertFalse($acl->isAllowed('guest', 'Foo\Bar\RpcController::do', 'POST'));
    }
}
