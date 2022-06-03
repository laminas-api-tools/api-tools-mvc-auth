<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\Adapter\Http\FileResolver;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FileResolverFactory implements FactoryInterface
{
    /**
     * Create and return a FileResolver instance, if configured.
     *
     * @param string             $requestedName
     * @param null|array         $options
     * @return false|FileResolver
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        if (! isset($config['api-tools-mvc-auth']['authentication']['http']['htdigest'])) {
            return false;
        }

        $htdigest = $config['api-tools-mvc-auth']['authentication']['http']['htdigest'];

        return new FileResolver($htdigest);
    }

    /**
     * Create and return a FileResolver instance, if configured (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @return false|FileResolver
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, FileResolver::class);
    }
}
