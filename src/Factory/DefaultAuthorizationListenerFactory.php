<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration.
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    /**
     * Create and return the default authorization listener.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     * @return DefaultAuthorizationListenerFactory
     * @throws ServiceNotCreatedException if the AuthorizationInterface service is missing.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (! $container->has(AuthorizationInterface::class)
            && ! $container->has(\ZF\MvcAuth\Authorization\AuthorizationInterface::class)
        ) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create %s service; no %s service available!',
                DefaultAuthorizationListener::class,
                AuthorizationInterface::class
            ));
        }

        return new DefaultAuthorizationListener($container->has(AuthorizationInterface::class) ? $container->get(AuthorizationInterface::class) : $container->get(\ZF\MvcAuth\Authorization\AuthorizationInterface::class));
    }

    /**
     * Create and return the default authorization listener (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DefaultAuthorizationListenerFactory
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DefaultAuthorizationListener::class);
    }
}
