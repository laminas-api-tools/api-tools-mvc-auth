<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */
namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use MongoClient;

final class AuthenticationOAuth2AdapterFactory
{
    /**
     * Intentionally empty and private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * Create and return an OAuth2Adapter instance.
     *
     * @param string|array $type
     * @param array $config
     * @param ServiceLocatorInterface $services
     * @return OAuth2Adapter
     * @throws ServiceNotCreatedException when missing details necessary to
     *     create instance and/or dependencies.
     */
    public static function factory($type, array $config, ServiceLocatorInterface $services)
    {
        if (! isset($config['storage']) || ! is_array($config['storage'])) {
            throw new ServiceNotCreatedException('Missing storage details for OAuth2 server');
        }

        return new OAuth2Adapter(
            OAuth2ServerFactory::factory($config['storage'], $services),
            $type
        );
    }
}
