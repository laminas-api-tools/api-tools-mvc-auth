<?php

namespace LaminasTest\ApiTools\MvcAuth\TestAsset;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;

class AuthenticationService
{
    /** @var IdentityInterface */
    protected $identity;

    /** @param IdentityInterface $identity */
    public function setIdentity($identity): void
    {
        $this->identity = $identity;
    }

    /** @return IdentityInterface */
    public function getIdentity()
    {
        return $this->identity;
    }

    /** @return $this */
    public function getStorage()
    {
        return $this;
    }

    /** @param IdentityInterface $identity */
    public function write($identity): void
    {
        $this->setIdentity($identity);
    }
}
