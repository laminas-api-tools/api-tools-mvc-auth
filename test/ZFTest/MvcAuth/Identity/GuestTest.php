<?php

namespace LaminasTest\ApiTools\MvcAuth\Identity;

use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use PHPUnit_Framework_TestCase as TestCase;

class GuestIdentityTest extends TestCase
{
    public function setUp()
    {
        $this->identity = new GuestIdentity();
    }

    public function testGuestIsAnIdentityType()
    {
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Identity\IdentityInterface', $this->identity);
    }

    public function testGuestImplementsAclRole()
    {
        $this->assertInstanceOf('Laminas\Permissions\Acl\Role\RoleInterface', $this->identity);
    }

    public function testGuestImplementsRbacRole()
    {
        $this->assertInstanceOf('Laminas\Permissions\Rbac\RoleInterface', $this->identity);
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
