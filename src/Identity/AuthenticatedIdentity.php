<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\AbstractRole as AbstractRbacRole;

class AuthenticatedIdentity extends AbstractRbacRole implements IdentityInterface
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
