<?php

namespace LaminasTest\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener;
use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\EventManager\EventManager;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Request;
use Laminas\Stdlib\Response;
use LaminasTest\ApiTools\MvcAuth\RouteMatchFactoryTrait;
use LaminasTest\ApiTools\MvcAuth\TestAsset\AuthenticationService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function array_shift;

class DefaultAuthorizationListenerTest extends TestCase
{
    use RouteMatchFactoryTrait;

    /** @var AuthenticationService */
    protected $authentication;

    /** @var Acl */
    protected $authorization;

    /** @var array */
    protected $restControllers = [];

    /** @var DefaultAuthorizationListener */
    protected $listener;

    /** @var MvcAuthEvent */
    protected $mvcAuthEvent;

    public function setUp()
    {
        // authentication service
        $this->authentication = new AuthenticationService();

        // authorization service
        $this->authorization = new AclAuthorization();
        $this->authorization->addRole('guest');
        $this->authorization->allow();

        // event for mvc and mvc-auth
        $routeMatch = $this->createRouteMatch([]);
        $request    = new HttpRequest();
        $response   = new HttpResponse();
        $container  = new ServiceManager();

        (new Config([
            'services' => [
                'EventManager'   => new EventManager(),
                'Authentication' => $this->authentication,
                'Authorization'  => $this->authorization,
                'Request'        => $request,
                'Response'       => $response,
            ],
        ]))->configureServiceManager($container);

        $application = $this->applicationFactory($container);

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response)
            ->setRouteMatch($routeMatch)
            ->setApplication($application);

        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);

        $this->listener = new DefaultAuthorizationListener($this->authorization);
    }

    public function applicationFactory(ServiceManager $container): Application
    {
        $r         = new ReflectionMethod(Application::class, '__construct');
        $arguments = $r->getParameters();
        $first     = array_shift($arguments);

        if ($first->getName() !== 'serviceManager') {
            // V2 construction
            return new Application([], $container);
        }

        return new Application($container);
    }

    public function testBailsEarlyOnInvalidRequest()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->getMvcEvent()->setRequest(new Request());
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testBailsEarlyOnInvalidResponse()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->getMvcEvent()->setResponse(new Response());
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testBailsEarlyOnMissingRouteMatch()
    {
        $listener = $this->listener;

        $request  = new HttpRequest();
        $response = new HttpResponse();
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response);
        $mvcAuthEvent = new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);

        $this->assertNull($listener($mvcAuthEvent));
    }

    public function testBailsEarlyOnMissingIdentity()
    {
        $listener = $this->listener;
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testBailsEarlyIfMvcAuthEventIsAuthorizedAlready()
    {
        $listener = $this->listener;
        // Setting identity to ensure we don't get a false positive
        $this->mvcAuthEvent->setIdentity(new GuestIdentity());
        $this->mvcAuthEvent->setIsAuthorized(true);
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testReturnsTrueIfIdentityPassesAcls()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->getMvcEvent()->getRouteMatch()->setParam('controller', 'Foo\Bar\Controller');
        $this->mvcAuthEvent->setIdentity(new GuestIdentity());
        $this->mvcAuthEvent->setResource('Foo\Bar\Controller');
        $this->assertTrue($listener($this->mvcAuthEvent));
    }

    public function testReturnsFalseIfIdentityFailsAcls()
    {
        $listener = $this->listener;
        $this->authorization->addResource('Foo\Bar\Controller::index');
        $this->authorization->deny('guest', 'Foo\Bar\Controller::index', 'POST');
        $this->mvcAuthEvent->setResource('Foo\Bar\Controller::index');
        $this->mvcAuthEvent->getMvcEvent()->getRequest()->setMethod('POST');
        $this->authentication->setIdentity(new GuestIdentity());
        $this->assertFalse($listener($this->mvcAuthEvent));
    }
}
