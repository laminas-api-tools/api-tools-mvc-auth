<?php

namespace LaminasTest\ApiTools\MvcAuth\Identity;

use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use PHPUnit_Framework_TestCase as TestCase;

class AuthenticatedIdentityTest extends TestCase
{
    public function setUp()
    {
        $this->authIdentity = (object) array(
            'name' => 'foo',
        );
        $this->identity = new AuthenticatedIdentity($this->authIdentity);
    }

    public function testAuthenticatedIsAnIdentityType()
    {
        $this->assertInstanceOf('Laminas\ApiTools\MvcAuth\Identity\IdentityInterface', $this->identity);
    }

    public function testAuthenticatedImplementsAclRole()
    {
        $this->assertInstanceOf('Laminas\Permissions\Acl\Role\RoleInterface', $this->identity);
    }

    public function testAuthenticatedImplementsRbacRole()
    {
        $this->assertInstanceOf('Laminas\Permissions\Rbac\RoleInterface', $this->identity);
    }

    public function testAuthenticatedAllowsSettingName()
    {
        $this->identity->setName($this->authIdentity->name);
        $this->assertEquals($this->authIdentity->name, $this->identity->getName());
        $this->assertEquals($this->authIdentity->name, $this->identity->getRoleId());
    }

    public function testAuthenticatedComposesAuthenticatedIdentity()
    {
        $this->assertSame($this->authIdentity, $this->identity->getAuthenticationIdentity());
    }
}
