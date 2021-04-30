<?php

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\Role;
use Laminas\Permissions\Rbac\RoleInterface;

class AuthenticatedIdentity extends Role implements IdentityInterface
{
    /** @var string|RoleInterface */
    protected $identity;

    /** @param string|RoleInterface $identity */
    public function __construct($identity)
    {
        $this->identity = $identity;
    }

    /** @return null|string */
    public function getRoleId()
    {
        return $this->name;
    }

    /** @return string|RoleInterface */
    public function getAuthenticationIdentity()
    {
        return $this->identity;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
