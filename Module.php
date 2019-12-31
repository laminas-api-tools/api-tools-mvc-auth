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
    protected $services;

    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Laminas\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__ . '/src/Laminas/MvcAuth/',
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

    public function onBootstrap(MvcEvent $mvcEvent)
    {
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

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION, $this->services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $this->services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $this->services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener'), 1000);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION, $this->services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener'));
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $this->services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener'));

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, array($this, 'onAuthenticationPost'), -1);
    }

    public function onAuthenticationPost(MvcAuthEvent $e)
    {
        $this->services->setService('api-identity', $e->getIdentity());
    }
}
