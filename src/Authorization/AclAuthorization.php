<?php

declare(strict_types=1);

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
