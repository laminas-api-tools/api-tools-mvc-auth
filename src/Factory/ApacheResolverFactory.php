<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ApacheResolverFactory implements FactoryInterface
{
    /**
     * Create and return an ApacheResolver instance.
     *
     * If appropriate configuration is not found, returns boolean false.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return false|ApacheResolver
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (false === $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        if (! isset($config['api-tools-mvc-auth']['authentication']['http']['htpasswd'])) {
            return false;
        }

        $htpasswd = $config['api-tools-mvc-auth']['authentication']['http']['htpasswd'];

        return new ApacheResolver($htpasswd);
    }

    /**
     * Create and return an ApacheResolve instance (v2).
     *
     * Exists for backwards compatibility only; proxies to __invoke().
     *
     * @param  ServiceLocatorInterface $container
     * @return false|ApacheResolver
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ApacheResolver::class);
    }
}
