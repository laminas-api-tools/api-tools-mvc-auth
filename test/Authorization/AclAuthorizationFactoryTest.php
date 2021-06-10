<?php

namespace LaminasTest\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization;
use Laminas\ApiTools\MvcAuth\Authorization\AclAuthorizationFactory;
use Laminas\Permissions\Acl\Acl;
use PHPUnit\Framework\TestCase;

class AclAuthorizationFactoryTest extends TestCase
{
    public function testFactoryGeneratesAclFromConfiguration(): void
    {
        $config = [
            [
                'resource'   => 'LaminasCon\V1\Rest\Session\Controller::collection',
                'privileges' => ['POST'],
            ],
            [
                'resource'   => 'LaminasCon\V1\Rest\Session\Controller::entity',
                'privileges' => ['PATCH', 'DELETE'],
            ],
            [
                'resource'   => 'LaminasCon\V1\Rpc\Message\Controller::message',
                'privileges' => ['POST'],
            ],
        ];

        $acl = AclAuthorizationFactory::factory($config);

        $this->assertInstanceOf(AclAuthorization::class, $acl);
        $this->assertInstanceOf(Acl::class, $acl);
        $this->assertTrue($acl->hasRole('guest'));
        $this->assertFalse($acl->hasRole('authenticated'));

        // Add a non-guest role to the ACL
        $acl->addRole('authenticated');

        // Test access to a collection that has ACLs in place
        $this->assertTrue(
            $acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::collection', 'POST')
        );
        $this->assertFalse($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::collection', 'POST'));
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::collection', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::collection', 'GET'));

        // Test access to a resource that has ACLs in place
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::entity', 'PATCH'));
        $this->assertFalse($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::entity', 'PATCH'));
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::entity', 'DELETE'));
        $this->assertFalse($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::entity', 'DELETE'));
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::entity', 'GET'));
        $this->assertTrue($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::entity', 'GET'));

        // Test access to an RPC service that has ACLs in place
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rpc\Message\Controller::message', 'POST'));
        $this->assertFalse($acl->isAllowed('guest', 'LaminasCon\V1\Rpc\Message\Controller::message', 'POST'));
    }

    public function testFactoryGeneratesBlacklistAclFromConfiguration(): void
    {
        $config = [
            'deny_by_default' => true,
            [
                'resource'   => 'LaminasCon\V1\Rest\Session\Controller::collection',
                'privileges' => ['GET'],
            ],
            [
                'resource'   => 'LaminasCon\V1\Rest\Session\Controller::entity',
                'privileges' => ['GET'],
            ],
            [
                'resource'   => 'LaminasCon\V1\Rpc\Message\Controller::message',
                'privileges' => ['GET'],
            ],
        ];

        $acl = AclAuthorizationFactory::factory($config);

        $this->assertInstanceOf(AclAuthorization::class, $acl);
        $this->assertInstanceOf(Acl::class, $acl);
        $this->assertTrue($acl->hasRole('guest'));
        $this->assertFalse($acl->hasRole('authenticated'));

        // Add a non-guest role to the ACL
        $acl->addRole('authenticated');

        // Test access to a collection that has ACLs in place
        $this->assertTrue(
            $acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::collection', 'POST')
        );
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::collection', 'GET'));
        $this->assertFalse($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::collection', 'POST'));
        $this->assertTrue($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::collection', 'GET'));

        // Test access to a resource that has ACLs in place
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::entity', 'PATCH'));
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rest\Session\Controller::entity', 'GET'));
        $this->assertFalse($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::entity', 'POST'));
        $this->assertTrue($acl->isAllowed('guest', 'LaminasCon\V1\Rest\Session\Controller::entity', 'GET'));

        // Test access to an RPC service that has ACLs in place
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rpc\Message\Controller::message', 'POST'));
        $this->assertTrue($acl->isAllowed('authenticated', 'LaminasCon\V1\Rpc\Message\Controller::message', 'GET'));
        $this->assertFalse($acl->isAllowed('guest', 'LaminasCon\V1\Rpc\Message\Controller::message', 'POST'));
        $this->assertTrue($acl->isAllowed('guest', 'LaminasCon\V1\Rpc\Message\Controller::message', 'GET'));
    }
}
