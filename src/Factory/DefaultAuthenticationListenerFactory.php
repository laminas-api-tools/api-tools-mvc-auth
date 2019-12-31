<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ApiTools\OAuth2\Factory\OAuth2ServerFactory as LaminasOAuth2ServerFactory;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\Server as OAuth2Server;
use RuntimeException;

/**
 * Factory for creating the DefaultAuthenticationListener from configuration
 */
class DefaultAuthenticationListenerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $services
     * @return DefaultAuthenticationListener
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $listener = new DefaultAuthenticationListener();

        $httpAdapter = $this->retrieveHttpAdapter($services);
        if ($httpAdapter) {
            $listener->attach($httpAdapter);
        }

        $oauth2Server = $this->createOAuth2Server($services);
        if ($oauth2Server) {
            $listener->attach($oauth2Server);
        }

        $authenticationTypes = $this->getAuthenticationTypes($services);
        if ($authenticationTypes) {
            $listener->addAuthenticationTypes($authenticationTypes);
        }

        $listener->setAuthMap($this->getAuthenticationMap($services));

        return $listener;
    }

    /**
     * @param  ServiceLocatorInterface $services
     * @throws ServiceNotCreatedException
     * @return false|HttpAdapter
     */
    protected function retrieveHttpAdapter(ServiceLocatorInterface $services)
    {
        // Allow applications to provide their own AuthHttpAdapter service; if none provided,
        // or no HTTP adapter configuration provided to api-tools-mvc-auth, we can stop early.
        $httpAdapter = $services->get('Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter');
        if ($httpAdapter === false) {
            return false;
        }

        // We must abort if no resolver was provided
        if (! $httpAdapter->getBasicResolver()
            && ! $httpAdapter->getDigestResolver()
        ) {
            return false;
        }

        $authService = $services->get('authentication');
        return new HttpAdapter($httpAdapter, $authService);
    }

    /**
     * Create an OAuth2 server by introspecting the config service
     *
     * @param  ServiceLocatorInterface $services
     * @throws \Laminas\ServiceManager\Exception\ServiceNotCreatedException
     * @return false|OAuth2Adapter
     */
    protected function createOAuth2Server(ServiceLocatorInterface $services)
    {
        if (! $services->has('Config')) {
            // If we don't have configuration, we cannot create an OAuth2 server.
            return false;
        }

        $config = $services->get('config');
        if (!isset($config['api-tools-oauth2']['storage'])
            || !is_string($config['api-tools-oauth2']['storage'])
            || !$services->has($config['api-tools-oauth2']['storage'])) {
              return false;
        }

        if ($services->has('Laminas\ApiTools\OAuth2\Service\OAuth2Server')) {
            // If the service locator already has a pre-configured OAuth2 server, use it.
            return new OAuth2Adapter($services->get('Laminas\ApiTools\OAuth2\Service\OAuth2Server'));
        }

        $factory = new LaminasOAuth2ServerFactory();

        try {
            $server = $factory->createService($services);
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

        return new OAuth2Adapter($server);
    }

    /**
     * Retrieve custom authentication types
     *
     * @param ServiceLocatorInterface $services
     * @return false|array
     */
    protected function getAuthenticationTypes(ServiceLocatorInterface $services)
    {
        if (! $services->has('config')) {
            return false;
        }

        $config = $services->get('config');
        if (! isset($config['api-tools-mvc-auth']['authentication']['types'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['types'])
        ) {
            return false;
        }

        return $config['api-tools-mvc-auth']['authentication']['types'];
    }

    protected function getAuthenticationMap(ServiceLocatorInterface $services)
    {
        if (! $services->has('config')) {
            return array();
        }

        $config = $services->get('config');
        if (! isset($config['api-tools-mvc-auth']['authentication']['map'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['map'])
        ) {
            return array();
        }

        return $config['api-tools-mvc-auth']['authentication']['map'];
    }
}
