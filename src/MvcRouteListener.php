<?php

namespace Laminas\ApiTools\MvcAuth;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as Response;

class MvcRouteListener extends AbstractListenerAggregate
{
    /**
     * @var AuthenticationService
     */
    protected $authentication;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var MvcAuthEvent
     */
    protected $mvcAuthEvent;

    /**
     * @param MvcAuthEvent $mvcAuthEvent
     * @param EventManagerInterface $events
     * @param AuthenticationService $authentication
     */
    public function __construct(
        MvcAuthEvent $mvcAuthEvent,
        EventManagerInterface $events,
        AuthenticationService $authentication
    ) {
        $this->attach($events);
        $mvcAuthEvent->setTarget($this);
        $this->mvcAuthEvent   = $mvcAuthEvent;
        $this->events         = $events;
        $this->authentication = $authentication;
    }

    /**
     * Attach listeners
     *
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'authentication'], -50);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'authenticationPost'], -51);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'authorization'], -600);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'authorizationPost'], -601);
    }

    /**
     * Trigger the authentication event
     *
     * @param MvcEvent $mvcEvent
     * @return null|Response
     */
    public function authentication(MvcEvent $mvcEvent)
    {
        if (! $mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $mvcAuthEvent = $this->mvcAuthEvent;
        $mvcAuthEvent->setName($mvcAuthEvent::EVENT_AUTHENTICATION);
        $responses    = $this->events->triggerEventUntil(function ($r) {
            return ($r instanceof Identity\IdentityInterface
                || $r instanceof Result
                || $r instanceof Response
            );
        }, $mvcAuthEvent);

        $result  = $responses->last();
        $storage = $this->authentication->getStorage();

        // If we have a response, return immediately
        if ($result instanceof Response) {
            return $result;
        }

        // Determine if the listener returned an identity
        if ($result instanceof Identity\IdentityInterface) {
            $storage->write($result);
        }

        // If we have a Result, we create an AuthenticatedIdentity from it
        if ($result instanceof Result
            && $result->isValid()
        ) {
            $mvcAuthEvent->setAuthenticationResult($result);
            $mvcAuthEvent->setIdentity(new Identity\AuthenticatedIdentity($result->getIdentity()));
            return;
        }

        $identity = $this->authentication->getIdentity();
        if ($identity === null && ! $mvcAuthEvent->hasAuthenticationResult()) {
            // if there is no Authentication identity or result, safe to assume we have a guest
            $mvcAuthEvent->setIdentity(new Identity\GuestIdentity());
            return;
        }

        if ($mvcAuthEvent->hasAuthenticationResult()
            && $mvcAuthEvent->getAuthenticationResult()->isValid()
        ) {
            $mvcAuthEvent->setIdentity(
                new Identity\AuthenticatedIdentity(
                    $mvcAuthEvent->getAuthenticationResult()->getIdentity()
                )
            );
        }

        if ($identity instanceof Identity\IdentityInterface) {
            $mvcAuthEvent->setIdentity($identity);
            return;
        }

        if ($identity !== null) {
            // identity found in authentication; we can assume we're authenticated
            $mvcAuthEvent->setIdentity(new Identity\AuthenticatedIdentity($identity));
            return;
        }
    }

    /**
     * Trigger the authentication.post event
     *
     * @param MvcEvent $mvcEvent
     * @return Response|mixed
     */
    public function authenticationPost(MvcEvent $mvcEvent)
    {
        if (! $mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $mvcAuthEvent = $this->mvcAuthEvent;
        $mvcAuthEvent->setName($mvcAuthEvent::EVENT_AUTHENTICATION_POST);

        $responses = $this->events->triggerEventUntil(function ($r) {
            return ($r instanceof Response);
        }, $mvcAuthEvent);

        return $responses->last();
    }

    /**
     * Trigger the authorization event
     *
     * @param MvcEvent $mvcEvent
     * @return null|Response
     */
    public function authorization(MvcEvent $mvcEvent)
    {
        if (! $mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $mvcAuthEvent = $this->mvcAuthEvent;
        $mvcAuthEvent->setName($mvcAuthEvent::EVENT_AUTHORIZATION);

        $responses = $this->events->triggerEventUntil(function ($r) {
            return (is_bool($r) || $r instanceof Response);
        }, $mvcAuthEvent);

        $result = $responses->last();

        if (is_bool($result)) {
            $mvcAuthEvent->setIsAuthorized($result);
            return;
        }

        if ($result instanceof Response) {
            return $result;
        }
    }

    /**
     * Trigger the authorization.post event
     *
     * @param MvcEvent $mvcEvent
     * @return null|Response
     */
    public function authorizationPost(MvcEvent $mvcEvent)
    {
        if (! $mvcEvent->getRequest() instanceof HttpRequest
            || $mvcEvent->getRequest()->isOptions()
        ) {
            return;
        }

        $mvcAuthEvent = $this->mvcAuthEvent;
        $mvcAuthEvent->setName($mvcAuthEvent::EVENT_AUTHORIZATION_POST);

        $responses = $this->events->triggerEventUntil(function ($r) {
            return ($r instanceof Response);
        }, $mvcAuthEvent);

        return $responses->last();
    }
}
