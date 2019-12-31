<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authorization\AclFactory;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener;
use Laminas\Http\Request;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    /**
     * Create the DefaultAuthorizationListener
     *
     * @param ServiceLocatorInterface $services
     * @return DefaultAuthorizationListener
     */
    public function createService(ServiceLocatorInterface $services)
    {
        if (!$services->has('Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface')) {
            throw new ServiceNotCreatedException(
                'Cannot create DefaultAuthorizationListener service; no Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface service available!'
            );
        }

        return new DefaultAuthorizationListener(
            $services->get('Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface')
        );
    }
}
