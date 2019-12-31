<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

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
