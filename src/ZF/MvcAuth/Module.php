<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth;

use Laminas\Mvc\MvcEvent;

class Module
{
    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Laminas\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__,
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $app      = $mvcEvent->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $authentication = $services->get('authentication');
        $mvcAuthEvent   = new MvcAuthEvent(
            $mvcEvent,
            $services->get('authentication'),
            $services->get('authorization')
        );
        $routeListener  = new MvcRouteListener(
            $mvcAuthEvent,
            $events,
            $authentication
        );

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION, $services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener'), 1000);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener'));
    }
}
