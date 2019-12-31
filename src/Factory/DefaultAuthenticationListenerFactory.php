<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\Server as OAuth2Server;

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
            $listener->setHttpAdapter($httpAdapter);
        }

        $oauth2Server = $this->createOAuth2Server($services);
        if ($oauth2Server) {
            $listener->setOauth2Server($oauth2Server);
        }

        return $listener;
    }

    /**
     * @param  ServiceLocatorInterface $services
     * @throws ServiceNotCreatedException
     * @return false|HttpAuth
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
        if (!$httpAdapter->getBasicResolver()
            && !$httpAdapter->getDigestResolver()
        ) {
            return false;
        }

        return $httpAdapter;
    }

    /**
     * Create an OAuth2 server by introspecting the config service
     *
     * @param  ServiceLocatorInterface $services
     * @throws \Laminas\ServiceManager\Exception\ServiceNotCreatedException
     * @return false|OAuth2Server
     */
    protected function createOAuth2Server(ServiceLocatorInterface $services)
    {
        if (!$services->has('config')) {
            return false;
        }

        $config = $services->get('config');
        if (!isset($config['api-tools-oauth2']['storage'])
            || !is_string($config['api-tools-oauth2']['storage'])
            || !$services->has($config['api-tools-oauth2']['storage'])
        ) {
            return false;
        }

        // If the service locator already has a pre-configured OAuth2 server, use it
        if ($services->has('Laminas\ApiTools\OAuth2\Service\OAuth2Server')) {
            return $services->get('Laminas\ApiTools\OAuth2\Service\OAuth2Server');
        }

        // There is no preconfigured OAuth2 server, so we must construct our own
        $storage = $services->get($config['api-tools-oauth2']['storage']);

        // Pass a storage object or array of storage objects to the OAuth2 server class
        $oauth2Server = new OAuth2Server($storage);

        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $oauth2Server->addGrantType(new ClientCredentials($storage));

        // Add the "Authorization Code" grant type
        $oauth2Server->addGrantType(new AuthorizationCode($storage));

        return $oauth2Server;
    }
}
