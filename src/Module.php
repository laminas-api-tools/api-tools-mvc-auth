<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth;

use Laminas\Http\Request as HttpRequest;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;

class Module
{
    protected $container;

    protected $mvcRouteListener;

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Register a listener for the mergeConfig event.
     *
     * @param ModuleManager $moduleManager
     */
    public function init(ModuleManager $moduleManager)
    {
        $events = $moduleManager->getEventManager();
        $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, [$this, 'onMergeConfig']);
    }

    /**
     * Override Laminas\ApiTools\OAuth2\Service\OAuth2Server service
     *
     * If the Laminas\ApiTools\OAuth2\Service\OAuth2Server is defined, and set to the
     * default, override it with the NamedOAuth2ServerFactory.
     *
     * @param ModuleEvent $e
     */
    public function onMergeConfig(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        $config         = $configListener->getMergedConfig(false);
        $service        = 'Laminas\ApiTools\OAuth2\Service\OAuth2Server';
        $default        = 'Laminas\ApiTools\OAuth2\Factory\OAuth2ServerFactory';

        if (! isset($config['service_manager']['factories'][$service])
            || $config['service_manager']['factories'][$service] !== $default
        ) {
            return;
        }

        $config['service_manager']['factories'][$service] = __NAMESPACE__ . '\Factory\NamedOAuth2ServerFactory';
        $configListener->setMergedConfig($config);
    }

    public function onBootstrap(MvcEvent $mvcEvent)
    {
        if (! $mvcEvent->getRequest() instanceof HttpRequest) {
            return;
        }

        $app      = $mvcEvent->getApplication();
        $events   = $app->getEventManager();
        $this->container = $app->getServiceManager();

        $authentication = $this->container->get('authentication');
        $mvcAuthEvent   = new MvcAuthEvent(
            $mvcEvent,
            $authentication,
            $this->container->get('authorization')
        );
        $this->mvcRouteListener = new MvcRouteListener($mvcAuthEvent, $events, $authentication);

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION,
            $this->container->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $this->container->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            $this->container->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener'),
            1000
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            $this->container->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $this->container->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener')
        );

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            [$this, 'onAuthenticationPost'],
            -1
        );
    }

    public function onAuthenticationPost(MvcAuthEvent $e)
    {
        if ($this->container->has('api-identity')) {
            return;
        }

        $this->container->setService('api-identity', $e->getIdentity());
    }

    /**
     * Retrieve the configured MvcRouteListener.
     *
     * @return null|MvcRouteListener
     */
    public function getMvcRouteListener()
    {
        return $this->mvcRouteListener;
    }
}
