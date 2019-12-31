<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */
namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceLocatorInterface;

final class AuthenticationHttpAdapterFactory
{
    /**
     * Intentionally empty and private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create an instance of Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter based on
     * the configuration provided and the registered AuthenticationService.
     *
     * @param string $type The base "type" the adapter will provide
     * @param array $config
     * @param ServiceLocatorInterface $services
     * @return HttpAdapter
     */
    public static function factory($type, array $config, ServiceLocatorInterface $services)
    {
        if (! $services->has('authentication')) {
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
            HttpAdapterFactory::factory($config['options'], $services),
            $services->get('authentication'),
            $type
        );
    }
}
