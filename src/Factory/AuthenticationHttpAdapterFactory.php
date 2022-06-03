<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;

use function is_array;

final class AuthenticationHttpAdapterFactory
{
    /**
     * Intentionally empty and private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create an instance of HttpAdapter based on the configuration provided
     * and the registered AuthenticationService.
     *
     * @param string $type The base "type" the adapter will provide
     * @param array $config
     * @return HttpAdapter
     */
    public static function factory($type, array $config, ContainerInterface $container)
    {
        if (! $container->has('authentication')) {
            throw new ServiceNotCreatedException(
                'Cannot create HTTP authentication adapter; missing AuthenticationService'
            );
        }

        if (! isset($config['options']) || ! is_array($config['options'])) {
            throw new ServiceNotCreatedException(
                'Cannot create HTTP authentication adapter; missing options'
            );
        }

        return new HttpAdapter(
            HttpAdapterFactory::factory($config['options'], $container),
            $container->get('authentication'),
            $type
        );
    }
}
