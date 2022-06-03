<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\Role;

class GuestIdentity extends Role implements IdentityInterface
{
    /** @var string */
    protected static $identity = 'guest';

    public function __construct()
    {
        parent::__construct(static::$identity);
    }

    /** @return string */
    public function getRoleId()
    {
        return static::$identity;
    }

    /** @return null */
    public function getAuthenticationIdentity()
    {
        return null;
    }
}
