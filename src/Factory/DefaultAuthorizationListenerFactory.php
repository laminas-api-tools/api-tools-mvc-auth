<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use function sprintf;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration.
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    /**
     * Create and return the default authorization listener.
     *
     * @param string             $requestedName
     * @param null|array         $options
     * @return DefaultAuthorizationListener
     * @throws ServiceNotCreatedException If the AuthorizationInterface service is missing.
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (
            ! $container->has(AuthorizationInterface::class)
            && ! $container->has(\ZF\MvcAuth\Authorization\AuthorizationInterface::class)
        ) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create %s service; no %s service available!',
                DefaultAuthorizationListener::class,
                AuthorizationInterface::class
            ));
        }

        $authorization = $container->has(AuthorizationInterface::class)
            ? $container->get(AuthorizationInterface::class)
            : $container->get(\ZF\MvcAuth\Authorization\AuthorizationInterface::class);

        return new DefaultAuthorizationListener($authorization);
    }

    /**
     * Create and return the default authorization listener (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @return DefaultAuthorizationListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DefaultAuthorizationListener::class);
    }
}
