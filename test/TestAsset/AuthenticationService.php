<?php

namespace LaminasTest\ApiTools\MvcAuth\TestAsset;

class AuthenticationService
{
    /** @var mixed */
    protected $identity;

    /** @param mixed $identity */
    public function setIdentity($identity): void
    {
        $this->identity = $identity;
    }

    /** @return mixed */
    public function getIdentity()
    {
        return $this->identity;
    }

    /** @return $this */
    public function getStorage()
    {
        return $this;
    }

    /** @param mixed $identity */
    public function write($identity): void
    {
        $this->setIdentity($identity);
    }
}
