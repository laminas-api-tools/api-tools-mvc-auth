<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Identity;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\InjectApplicationEventInterface;

class IdentityPlugin extends AbstractPlugin
{
    public function __invoke()
    {
        $controller = $this->getController();
        if (! $controller instanceof InjectApplicationEventInterface) {
            return new GuestIdentity();
        }

        $event    = $controller->getEvent();
        $identity = $event->getParam(__NAMESPACE__);

        if (! $identity instanceof IdentityInterface) {
            return new GuestIdentity();
        }

        return $identity;
    }
}
