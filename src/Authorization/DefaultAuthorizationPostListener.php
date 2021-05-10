<?php

namespace Laminas\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Response as HttpResponse;

class DefaultAuthorizationPostListener
{
    /**
     * Determine if we have an authorization failure, and, if so, return a 403 response
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return null|\Laminas\Http\Response
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $response = $mvcEvent->getResponse();

        if ($mvcAuthEvent->isAuthorized()) {
            if ($response instanceof HttpResponse) {
                if ($response->getStatusCode() != 200) {
                    $response->setStatusCode(200);
                }
            }
            return;
        }

        if (! $response instanceof HttpResponse) {
            return $response;
        }

        $response->setStatusCode(403);
        $response->setReasonPhrase('Forbidden');
        return $response;
    }
}
