<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Permissions\Acl\Acl;

class DefaultAuthorizationListener
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * Array of controller_service_name/identifier_name pairs
     *
     * @var array
     */
    protected $restControllers;

    /**
     * @param Acl $acl
     * @param array $restControllers
     */
    public function __construct(AuthorizationInterface $authorization, array $restControllers = array())
    {
        $this->authorization   = $authorization;
        $this->restControllers = $restControllers;
    }

    /**
     * Attempt to authorize the discovered identity based on the ACLs present
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return bool
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        if ($mvcAuthEvent->isAuthorized()) {
            return;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();

        $request  = $mvcEvent->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        $response  = $mvcEvent->getResponse();
        if (!$response instanceof Response) {
            return;
        }

        $routeMatch = $mvcEvent->getRouteMatch();
        if (!$routeMatch instanceof RouteMatch) {
            return;
        }

        $identity = $mvcAuthEvent->getIdentity();
        if (!$identity instanceof IdentityInterface) {
            return;
        }

        $resource = $mvcAuthEvent->getResource();
        $identity = $mvcAuthEvent->getIdentity();
        return $this->authorization->isAuthorized($identity, $resource, $request->getMethod());
    }
}
