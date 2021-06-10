<?php

namespace LaminasTest\ApiTools\MvcAuth\Identity;

use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\Permissions\Acl\Role\RoleInterface as AclRoleInterface;
use Laminas\Permissions\Rbac\RoleInterface as RbacRoleInterface;
use PHPUnit\Framework\TestCase;

class AuthenticatedIdentityTest extends TestCase
{
    public function setUp(): void
    {
        $this->authIdentity = (object) [
            'name' => 'foo',
        ];
        $this->identity     = new AuthenticatedIdentity($this->authIdentity);
    }

    public function testAuthenticatedIsAnIdentityType(): void
    {
        $this->assertInstanceOf(IdentityInterface::class, $this->identity);
    }

    public function testAuthenticatedImplementsAclRole(): void
    {
        $this->assertInstanceOf(AclRoleInterface::class, $this->identity);
    }

    public function testAuthenticatedImplementsRbacRole(): void
    {
        $this->assertInstanceOf(RbacRoleInterface::class, $this->identity);
    }

    public function testAuthenticatedAllowsSettingName(): void
    {
        $this->identity->setName($this->authIdentity->name);
        $this->assertEquals($this->authIdentity->name, $this->identity->getName());
        $this->assertEquals($this->authIdentity->name, $this->identity->getRoleId());
    }

    public function testAuthenticatedComposesAuthenticatedIdentity(): void
    {
        $this->assertSame($this->authIdentity, $this->identity->getAuthenticationIdentity());
    }
}
