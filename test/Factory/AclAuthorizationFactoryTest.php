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

    public function testCreatingOAuth2ServerFromStorageService()
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
}

