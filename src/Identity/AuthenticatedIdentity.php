<?php

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\Role;

class AuthenticatedIdentity extends Role implements IdentityInterface
{
    protected $identity;

    public function __construct($identity)
    {
        $this->identity = $identity;
    }

    public function getRoleId()
    {
        return $this->name;
    }

    public function getAuthenticationIdentity()
    {
        return $this->identity;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
