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
    protected $services;

    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Laminas\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__ . '/src/',
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Register a listener for the mergeConfig event.
     *
     * @param ModuleManager $moduleManager
     */
    public function init(ModuleManager $moduleManager)
    {
        $events = $moduleManager->getEventManager();
        $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, array($this, 'onMergeConfig'));
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
        $this->services = $app->getServiceManager();

        $authentication = $this->services->get('authentication');
        $mvcAuthEvent   = new MvcAuthEvent(
            $mvcEvent,
            $authentication,
            $this->services->get('authorization')
        );
        $routeListener  = new MvcRouteListener(
            $mvcAuthEvent,
            $events,
            $authentication
        );

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION,
            $this->services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $this->services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            $this->services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener'),
            1000
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            $this->services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $this->services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener')
        );

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            array($this, 'onAuthenticationPost'),
            -1
        );
    }

    public function onAuthenticationPost(MvcAuthEvent $e)
    {
        if ($this->services->has('api-identity')) {
            return;
        }

        $this->services->setService('api-identity', $e->getIdentity());
    }
}
