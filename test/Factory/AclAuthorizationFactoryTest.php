<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Factory\AclAuthorizationFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class AclAuthorizationFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory  = new AclAuthorizationFactory();
    }

    public function testCanCreateWhitelistAcl()
    {
        $config = array('api-tools-mvc-auth' => array('authorization' => array(
            'Foo\Bar\RestController' => array(
                'entity' => array(
                    'GET'    => false,
                    'POST'   => false,
                    'PUT'    => true,
                    'PATCH'  => true,
                    'DELETE' => true,
                ),
                'collection' => array(
                    'GET'    => false,
                    'POST'   => true,
                    'PUT'    => false,
                    'PATCH'  => false,
                    'DELETE' => false,
                ),
            ),
            'Foo\Bar\RpcController' => array(
                'actions' => array(
                    'do' => array(
                        'GET'    => false,
                        'POST'   => true,
                        'PUT'    => false,
                        'PATCH'  => false,
                        'DELETE' => false,
                    ),
                ),
            ),
        )));
        $this->services->setService('config', $config);

        $acl = $this->factory->createService($this->services);

        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization', $acl);

        $authorizations = $config['api-tools-mvc-auth']['authorization'];

        foreach ($authorizations as $resource => $rules) {
            switch (true) {
                case (array_key_exists('entity', $rules)):
                    foreach ($rules['entity'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::entity', $method));
                    }
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
        $config = array('api-tools-mvc-auth' => array('authorization' => array(
            'deny_by_default' => true,
            'Foo\Bar\RestController' => array(
                'entity' => array(
                    'GET'    => false,
                    'POST'   => false,
                    'PUT'    => true,
                    'PATCH'  => true,
                    'DELETE' => true,
                ),
                'collection' => array(
                    'GET'    => false,
                    'POST'   => true,
                    'PUT'    => false,
                    'PATCH'  => false,
                    'DELETE' => false,
                ),
            ),
            'Foo\Bar\RpcController' => array(
                'actions' => array(
                    'do' => array(
                        'GET'    => false,
                        'POST'   => true,
                        'PUT'    => false,
                        'PATCH'  => false,
                        'DELETE' => false,
                    ),
                ),
            ),
        )));
        $this->services->setService('config', $config);

        $acl = $this->factory->createService($this->services);

        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization', $acl);

        $authorizations = $config['api-tools-mvc-auth']['authorization'];
        unset($authorizations['deny_by_default']);

        foreach ($authorizations as $resource => $rules) {
            switch (true) {
                case (array_key_exists('entity', $rules)):
                    foreach ($rules['entity'] as $method => $expected) {
                        $assertion = 'assert' . ($expected ? 'False' : 'True');
                        $this->$assertion($acl->isAllowed('guest', $resource . '::entity', $method));
                    }
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
        $config = array('api-tools-mvc-auth' => array('authorization' => array(
            'deny_by_default' => true,
            'Foo\Bar\RestController' => array(
                'entity' => array(
                    'GET'    => false,
                    'POST'   => false,
                ),
                'collection' => array(
                    'GET'    => false,
                    'PUT'    => false,
                    'PATCH'  => false,
                    'DELETE' => false,
                ),
            ),
            'Foo\Bar\RpcController' => array(
                'actions' => array(
                    'do' => array(
                        'GET'    => false,
                        'PUT'    => false,
                        'PATCH'  => false,
                        'DELETE' => false,
                    ),
                ),
            ),
        )));
        $this->services->setService('config', $config);

        $acl = $this->factory->createService($this->services);

        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization', $acl);

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
}
