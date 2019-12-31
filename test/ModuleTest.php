<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\ApiTools\MvcAuth\Module;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

class ModuleTest extends TestCase
{
    public function setUp()
    {
        $this->mvcEvent = $mvcEvent = $this->prophesize('Laminas\Mvc\MvcEvent');
        $this->module = new Module();
    }

    public function setUpApplication()
    {
        $services = $this->setUpServices();
        $events   = $this->setUpEvents();

        $application = $this->prophesize('Laminas\Mvc\Application');
        $application->getEventManager()->will([$events, 'reveal']);
        $application->getServiceManager()->will([$services, 'reveal']);

        return $application;
    }

    public function setUpServices()
    {
        $authentication = $this->prophesize('Laminas\Authentication\AuthenticationService');
        $authorization  = $this->prophesize('Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface');
        $defaultAuthenticationListener = $this->prophesize(
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener'
        );
        $defaultAuthenticationPostListener = $this->prophesize(
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener'
        );
        $defaultResourceResolverListener = $this->prophesize(
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener'
        );
        $defaultAuthorizationListener = $this->prophesize(
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener'
        );
        $defaultAuthorizationPostListener = $this->prophesize(
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener'
        );

        $services = $this->prophesize('Laminas\ServiceManager\ServiceLocatorInterface');
        $services->get('authentication')->will([$authentication, 'reveal']);
        $services->get('authorization')->will([$authorization, 'reveal']);
        $services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener')
            ->will([$defaultAuthenticationListener, 'reveal']);
        $services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener')
            ->will([$defaultAuthenticationPostListener, 'reveal']);
        $services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener')
            ->will([$defaultResourceResolverListener, 'reveal']);
        $services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener')
            ->will([$defaultAuthorizationListener, 'reveal']);
        $services->get('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener')
            ->will([$defaultAuthorizationPostListener, 'reveal']);

        return $services;
    }

    public function setUpEvents()
    {
        $events = $this->prophesize('Laminas\EventManager\EventManagerInterface');

        $events->attach(Argument::type('Laminas\ApiTools\MvcAuth\MvcRouteListener'));

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION,
            Argument::type('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            Argument::type('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            Argument::type('Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener'),
            1000
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION,
            Argument::type('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            Argument::type('Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener')
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            Argument::is([$this->module, 'onAuthenticationPost']),
            -1
        );

        return $events;
    }

    public function testOnBootstrapReturnsEarlyForNonHttpEvents()
    {
        $request = $this->prophesize('Laminas\Stdlib\RequestInterface');
        $this->mvcEvent->getRequest()->will([$request, 'reveal']);
        $this->module->onBootstrap($this->mvcEvent->reveal());
    }

    public function testOnBootstrapAttachesListeners()
    {
        $mvcEvent    = $this->mvcEvent;
        $request     = $this->prophesize('Laminas\Http\Request');
        $application = $this->setUpApplication();
        $mvcEvent->getRequest()->will([$request, 'reveal']);
        $mvcEvent->getApplication()->will([$application, 'reveal']);
        $this->module->onBootstrap($mvcEvent->reveal());
    }
}
