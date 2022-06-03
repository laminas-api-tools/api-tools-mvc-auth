<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\Authentication\Result;
use Laminas\EventManager\Event;
use Laminas\Mvc\MvcEvent;

class MvcAuthEvent extends Event
{
    public const EVENT_AUTHENTICATION      = 'authentication';
    public const EVENT_AUTHENTICATION_POST = 'authentication.post';
    public const EVENT_AUTHORIZATION       = 'authorization';
    public const EVENT_AUTHORIZATION_POST  = 'authorization.post';

    /** @var MvcEvent */
    protected $mvcEvent;

    /** @var mixed */
    protected $authentication;

    /** @var Result */
    protected $authenticationResult;

    /** @var mixed */
    protected $authorization;

    /**
     * Whether or not authorization has completed/succeeded
     *
     * @var bool
     */
    protected $authorized = false;

    /**
     * The resource used for authorization queries
     *
     * @var mixed
     */
    protected $resource;

    /**
     * @param mixed    $authentication
     * @param mixed    $authorization
     */
    public function __construct(MvcEvent $mvcEvent, $authentication, $authorization)
    {
        $this->mvcEvent       = $mvcEvent;
        $this->authentication = $authentication;
        $this->authorization  = $authorization;
    }

    /**
     * @return mixed
     */
    public function getAuthenticationService()
    {
        return $this->authentication;
    }

    /**
     * @return bool
     */
    public function hasAuthenticationResult()
    {
        return $this->authenticationResult !== null;
    }

    /**
     * @return self
     */
    public function setAuthenticationResult(Result $result)
    {
        $this->authenticationResult = $result;
        return $this;
    }

    /**
     * @return null|Result
     */
    public function getAuthenticationResult()
    {
        return $this->authenticationResult;
    }

    /**
     * @return mixed
     */
    public function getAuthorizationService()
    {
        return $this->authorization;
    }

    /**
     * @return MvcEvent
     */
    public function getMvcEvent()
    {
        return $this->mvcEvent;
    }

    /**
     * @return mixed|null
     */
    public function getIdentity()
    {
        return $this->authentication->getIdentity();
    }

    /**
     * @return $this
     */
    public function setIdentity(IdentityInterface $identity)
    {
        $this->authentication->getStorage()->write($identity);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param  mixed $resource
     * @return self
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

    /**
     * @param  bool $flag
     * @return self
     */
    public function setIsAuthorized($flag)
    {
        $this->authorized = (bool) $flag;
        return $this;
    }
}
