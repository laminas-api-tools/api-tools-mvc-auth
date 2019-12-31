<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\Permissions\Acl\Acl;

/**
 * Authorization implementation that uses the ACL component
 */
class AclAuthorization extends Acl implements AuthorizationInterface
{
    /**
     * Is the provided identity authorized for the given privilege on the given resource?
     *
     * If the resource does not exist, adds it, the proxies to isAllowed().
     *
     * @param IdentityInterface $identity
     * @param mixed $resource
     * @param mixed $privilege
     * @return bool
     */
    public function isAuthorized(IdentityInterface $identity, $resource, $privilege)
    {
        if (null !== $resource && (! $this->hasResource($resource))) {
            $this->addResource($resource);
        }

        if (! $this->hasRole($identity)) {
            $this->addRole($identity);
        }

        return $this->isAllowed($identity, $resource, $privilege);
    }
}
