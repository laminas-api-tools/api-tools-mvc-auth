<?php

namespace LaminasTest\ApiTools\MvcAuth\Identity;

use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\Permissions\Acl\Role\RoleInterface as AclRoleInterface;
use Laminas\Permissions\Rbac\RoleInterface as RbacRoleInterface;
use PHPUnit\Framework\TestCase;

class GuestIdentityTest extends TestCase
{
    public function setUp(): void
    {
        $this->identity = new GuestIdentity();
    }

    public function testGuestIsAnIdentityType()
    {
        $this->assertInstanceOf(IdentityInterface::class, $this->identity);
    }

    public function testGuestImplementsAclRole()
    {
        $this->assertInstanceOf(AclRoleInterface::class, $this->identity);
    }

    public function testGuestImplementsRbacRole()
    {
        $this->assertInstanceOf(RbacRoleInterface::class, $this->identity);
    }

    public function testGuestRoleIdIsGuest()
    {
        $this->assertEquals('guest', $this->identity->getRoleId());
    }

    public function testGuestRoleNameIsGuest()
    {
        $this->assertEquals('guest', $this->identity->getName());
    }

    public function testGuestDoesNotComposeAuthenticationIdentity()
    {
        $this->assertNull($this->identity->getAuthenticationIdentity());
    }
}
