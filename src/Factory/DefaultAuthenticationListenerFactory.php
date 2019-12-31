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
use Laminas\ApiTools\OAuth2\Factory\OAuth2ServerFactory as LaminasOAuth2ServerFactory;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use RuntimeException;

/**
 * Factory for creating the DefaultAuthenticationListener from configuration.
 */
class DefaultAuthenticationListenerFactory implements FactoryInterface
{
    /**
     * Create and return a DefaultAuthenticationListener.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param null|array         $options
     * @return DefaultAuthenticationListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $listener = new DefaultAuthenticationListener();

        $httpAdapter = $this->retrieveHttpAdapter($container);
        if ($httpAdapter) {
            $listener->attach($httpAdapter);
        }

        $oauth2Server = $this->createOAuth2Server($container);
        if ($oauth2Server) {
            $listener->attach($oauth2Server);
        }

        $authenticationTypes = $this->getAuthenticationTypes($container);
        if ($authenticationTypes) {
            $listener->addAuthenticationTypes($authenticationTypes);
        }

        $listener->setAuthMap($this->getAuthenticationMap($container));

        return $listener;
    }

    /**
     * Create and return a DefaultAuthenticationListener (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DefaultAuthenticationListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DefaultAuthenticationListener::class);
    }

    /**
     * @param ContainerInterface $services
     * @return false|HttpAdapter
     */
    protected function retrieveHttpAdapter(ContainerInterface $container)
    {
        // Allow applications to provide their own AuthHttpAdapter service; if none provided,
        // or no HTTP adapter configuration provided to api-tools-mvc-auth, we can stop early.

        $httpAdapter = $container->get('Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter');

        if ($httpAdapter === false) {
            return false;
        }

        // We must abort if no resolver was provided
        if (! $httpAdapter->getBasicResolver()
            && ! $httpAdapter->getDigestResolver()
        ) {
            return false;
        }

        $authService = $container->get('authentication');

        return new HttpAdapter($httpAdapter, $authService);
    }

    /**
     * Create an OAuth2 server by introspecting the config service
     *
     * @param ContainerInterface $container
     * @return false|OAuth2Adapter
     */
    protected function createOAuth2Server(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            // If we don't have configuration, we cannot create an OAuth2 server.
            return false;
        }

        $config = $container->get('config');
        if (! isset($config['api-tools-oauth2']['storage'])
            || ! is_string($config['api-tools-oauth2']['storage'])
            || ! $container->has($config['api-tools-oauth2']['storage'])
        ) {
            return false;
        }

        if ($container->has('Laminas\ApiTools\OAuth2\Service\OAuth2Server')) {
            // If the service locator already has a pre-configured OAuth2 server, use it.
            $factory = $container->get('Laminas\ApiTools\OAuth2\Service\OAuth2Server');

            return new OAuth2Adapter($factory());
        }

        $factory = new LaminasOAuth2ServerFactory();

        try {
            $serverFactory = $factory($container);
        } catch (RuntimeException $e) {
            // These are exceptions specifically thrown from the
            // Laminas\ApiTools\OAuth2\Factory\OAuth2ServerFactory when essential
            // configuration is missing.
            switch (true) {
                case strpos($e->getMessage(), 'missing'):
                    return false;
                case strpos($e->getMessage(), 'string or array'):
                    return false;
                default:
                    // Any other RuntimeException at this point we don't know
                    // about and need to re-throw.
                    throw $e;
            }
        }

        return new OAuth2Adapter($serverFactory(null));
    }

    /**
     * Retrieve custom authentication types
     *
     * @param ContainerInterface $container
     * @return array|false
     */
    protected function getAuthenticationTypes(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');
        if (! isset($config['api-tools-mvc-auth']['authentication']['types'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['types'])
        ) {
            return false;
        }

        return $config['api-tools-mvc-auth']['authentication']['types'];
    }

    /**
     * @param ContainerInterface $container
     * @return array
     */
    protected function getAuthenticationMap(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');
        if (! isset($config['api-tools-mvc-auth']['authentication']['map'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['map'])
        ) {
            return [];
        }

        return $config['api-tools-mvc-auth']['authentication']['map'];
    }
}
