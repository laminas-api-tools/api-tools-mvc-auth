<?php

namespace Laminas\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;

interface AuthorizationInterface
{
    /**
     * Whether or not the given identity has the given privilege on the given resource.
     *
     * @param IdentityInterface $identity
     * @param mixed $resource
     * @param mixed $privilege
     * @return bool
     */
    public function isAuthorized(IdentityInterface $identity, $resource, $privilege);
}
