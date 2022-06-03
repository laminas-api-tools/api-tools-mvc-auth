<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DefaultAuthHttpAdapterFactory from configuration.
 */
class DefaultAuthHttpAdapterFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param string     $requestedName
     * @param null|array $options
     * @return HttpAuth|false
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        // If no configuration present, nothing to create
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        // If no HTTP adapter configuration present, nothing to create
        if (! isset($config['api-tools-mvc-auth']['authentication']['http'])) {
            return false;
        }

        return HttpAdapterFactory::factory($config['api-tools-mvc-auth']['authentication']['http'], $container);
    }

    /**
     * Create and return an HTTP authentication adapter instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @return HttpAuth|false
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, HttpAuth::class);
    }
}
