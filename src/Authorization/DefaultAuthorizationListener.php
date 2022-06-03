<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Router\RouteMatch as V2RouteMatch;
use Laminas\Router\RouteMatch;

class DefaultAuthorizationListener
{
    /** @var AuthorizationInterface */
    protected $authorization;

    public function __construct(AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Attempt to authorize the discovered identity based on the ACLs present
     *
     * @return bool
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        if ($mvcAuthEvent->isAuthorized()) {
            return;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();

        $request = $mvcEvent->getRequest();
        if (! $request instanceof Request) {
            return;
        }

        $response = $mvcEvent->getResponse();
        if (! $response instanceof Response) {
            return;
        }

        $routeMatch = $mvcEvent->getRouteMatch();
        if (! ($routeMatch instanceof RouteMatch || $routeMatch instanceof V2RouteMatch)) {
            return;
        }

        $identity = $mvcAuthEvent->getIdentity();
        if (! $identity instanceof IdentityInterface) {
            return;
        }

        $resource = $mvcAuthEvent->getResource();
        $identity = $mvcAuthEvent->getIdentity();
        return $this->authorization->isAuthorized($identity, $resource, $request->getMethod());
    }
}
