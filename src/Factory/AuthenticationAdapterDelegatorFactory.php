<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */
namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ServiceManager\DelegatorFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class AuthenticationAdapterDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * Decorate the DefaultAuthenticationListener.
     *
     * Attaches adapters as listeners if present in configuration.
     *
     * @param  ContainerInterface $container
     * @param  string             $name
     * @param  callable           $callback
     * @param  null|array         $options
     * @return DefaultAuthenticationListener
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $listener = $callback();

        $config = $container->get('config');
        if (! isset($config['api-tools-mvc-auth']['authentication']['adapters'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['adapters'])
        ) {
            return $listener;
        }

        foreach ($config['api-tools-mvc-auth']['authentication']['adapters'] as $type => $data) {
            $this->attachAdapterOfType($type, $data, $container, $listener);
        }

        return $listener;
    }

    /**
     * Decorate the DefaultAuthenticationListener (v2)
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @param callable $callback
     * @return DefaultAuthenticationListener
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }

    /**
     * Attach an adaper to the listener as described by $type and $data.
     *
     * @param string $type
     * @param array $adapterConfig
     * @param ContainerInterface $container
     * @param DefaultAuthenticationListener $listener
     */
    private function attachAdapterOfType(
        $type,
        array $adapterConfig,
        ContainerInterface $container,
        DefaultAuthenticationListener $listener
    ) {
        if (! isset($adapterConfig['adapter'])
            || ! is_string($adapterConfig['adapter'])
        ) {
            return;
        }

        switch ($adapterConfig['adapter']) {
            case HttpAdapter::class:
                $adapter = AuthenticationHttpAdapterFactory::factory($type, $adapterConfig, $container);
                break;
            case OAuth2Adapter::class:
                $adapter = AuthenticationOAuth2AdapterFactory::factory($type, $adapterConfig, $container);
                break;
            default:
                $adapter = false;
                break;
        }

        if (! $adapter) {
            return;
        }

        $listener->attach($adapter);
    }
}
