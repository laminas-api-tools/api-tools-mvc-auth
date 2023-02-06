<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\Role;

class AuthenticatedIdentity extends Role implements IdentityInterface
{
    /** @var mixed */
    protected $identity;

    /** @param mixed $identity */
    public function __construct($identity)
    {
        $this->identity = $identity;
    }

    /** @return null|string */
    public function getRoleId()
    {
        return $this->name;
    }

    /** @return mixed */
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
