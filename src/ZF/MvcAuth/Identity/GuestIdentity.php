<?php

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\AbstractRole as AbstractRbacRole;

class GuestIdentity extends AbstractRbacRole implements IdentityInterface
{
    protected static $identity = 'guest';

    public function __construct()
    {
        $this->name = static::$identity;
    }

    public function getRoleId()
    {
        return static::$identity;
    }

    public function getAuthenticationIdentity()
    {
        return null;
    }
}
