<?php

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener;
use Laminas\ApiTools\MvcAuth\Module;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\RequestInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function method_exists;

class ModuleTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    protected function createApplication(ServiceManager $services, EventManagerInterface $events): Application
    {
        $r = new ReflectionMethod(Application::class, '__construct');
        if ($r->getNumberOfRequiredParameters() === 2) {
            // laminas-mvc v2
            return new Application([], $services, $events);
        }

        // laminas-mvc v3
        return new Application($services, $events);
    }

    /** @psalm-param array<string, mixed> $config */
    protected function createServiceManager(array $config): ServiceManager
    {
        if (method_exists(ServiceManager::class, 'configure')) { // v3
            // laminas-servicemanager v3
            return new ServiceManager($config['service_manager']);
        }

        // laminas-servicemanager v2
        $servicesConfig = new ServiceManagerConfig($config['service_manager']);
        return new ServiceManager($servicesConfig);
    }

    public function testOnBootstrapReturnsEarlyForNonHttpEvents()
    {
        $mvcEvent = $this->prophesize(MvcEvent::class);
        $module   = new Module();

        $request = $this->prophesize(RequestInterface::class)->reveal();
        $mvcEvent->getRequest()->willReturn($request);
        $module->onBootstrap($mvcEvent->reveal());

        $this->assertNull($module->getContainer());
    }

    /**
     * @psalm-return array<string, array{
     *     0: callable(MvcEvent|MvcAuthEvent):null|Response,
     *     1: int,
     *     2: MvcEvent::EVENT_*|MvcAuthEvent::EVENT_*
     *     3: EventManager
     * }>
     */
    public function expectedListeners(): array
    {
        $module   = new Module();
        $config   = $module->getConfig();
        $request  = $this->prophesize(Request::class)->reveal();
        $response = $this->prophesize(Response::class)->reveal();

        $services = $this->createServiceManager($config);
        $services->setService('Request', $request);
        $services->setService('Response', $response);
        $services->setService('config', $config);

        $events = new EventManager();

        $application = $this->createApplication($services, $events);

        $mvcEvent = new MvcEvent(MvcEvent::EVENT_BOOTSTRAP);
        $mvcEvent->setApplication($application);
        $mvcEvent->setRequest($request);
        $mvcEvent->setResponse($response);

        $module->onBootstrap($mvcEvent);

        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'mvc-route-authentication'         => [[$module->getMvcRouteListener(), 'authentication'], -50, MvcEvent::EVENT_ROUTE, $events],
            'mvc-route-authentication-post'    => [[$module->getMvcRouteListener(), 'authenticationPost'], -51, MvcEvent::EVENT_ROUTE, $events],
            'mvc-route-authorization'          => [[$module->getMvcRouteListener(), 'authorization'], -600, MvcEvent::EVENT_ROUTE, $events],
            'mvc-route-authorization-post'     => [[$module->getMvcRouteListener(), 'authorizationPost'], -601, MvcEvent::EVENT_ROUTE, $events],
            'authentication'                   => [$services->get(DefaultAuthenticationListener::class),        1, MvcAuthEvent::EVENT_AUTHENTICATION,      $events],
            'authentication-post'              => [$services->get(DefaultAuthenticationPostListener::class),    1, MvcAuthEvent::EVENT_AUTHENTICATION_POST, $events],
            'resource-resoolver-authorization' => [$services->get(DefaultResourceResolverListener::class),   1000, MvcAuthEvent::EVENT_AUTHORIZATION,       $events],
            'authorization'                    => [$services->get(DefaultAuthorizationListener::class),         1, MvcAuthEvent::EVENT_AUTHORIZATION,       $events],
            'authorization-post'               => [$services->get(DefaultAuthorizationPostListener::class),     1, MvcAuthEvent::EVENT_AUTHORIZATION_POST,  $events],
            'module-authentication-post'       => [[$module, 'onAuthenticationPost'], -1, MvcAuthEvent::EVENT_AUTHENTICATION_POST, $events],
        ];
        // phpcs:enable
    }

    /**
     * @dataProvider expectedListeners
     * @psalm-param callable(MvcEvent|MvcAuthEvent):null|Response $listener
     * @psalm-param MvcEvent::EVENT_*|MvcAuthEvent::EVENT_* $event
     */
    public function testOnBootstrapAttachesListeners(
        callable $listener,
        int $priority,
        string $event,
        EventManager $events
    ): void {
        $this->assertListenerAtPriority(
            $listener,
            $priority,
            $event,
            $events
        );
    }
}
