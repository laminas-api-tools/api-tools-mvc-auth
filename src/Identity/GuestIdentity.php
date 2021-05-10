<?php

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\Role;

class GuestIdentity extends Role implements IdentityInterface
{
    protected static $identity = 'guest';

    public function __construct()
    {
        parent::__construct(static::$identity);
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
