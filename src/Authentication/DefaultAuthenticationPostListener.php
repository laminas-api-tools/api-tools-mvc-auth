<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Authentication;

use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Response as HttpResponse;

class DefaultAuthenticationPostListener
{
    /**
     * Determine if we have an authentication failure, and, if so, return a 401 response
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return null|\Laminas\Http\Response
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        if (! $mvcAuthEvent->hasAuthenticationResult()) {
            return;
        }

        $authResult = $mvcAuthEvent->getAuthenticationResult();
        if ($authResult->isValid()) {
            return;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $response = $mvcEvent->getResponse();
        if (! $response instanceof HttpResponse) {
            return $response;
        }

        $response->setStatusCode(401);
        $response->setReasonPhrase('Unauthorized');
        return $response;
    }
}
