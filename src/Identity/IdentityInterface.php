<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Acl\Role\RoleInterface as AclRoleInterface;
use Laminas\Permissions\Rbac\RoleInterface as RbacRoleInterface;

interface IdentityInterface extends
    AclRoleInterface,
    RbacRoleInterface
{
    public function getAuthenticationIdentity();
}
