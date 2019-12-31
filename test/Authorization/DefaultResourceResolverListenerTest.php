<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Stdlib\Request;
use Laminas\Stdlib\Response;
use LaminasTest\ApiTools\MvcAuth\TestAsset;
use PHPUnit_Framework_TestCase as TestCase;

class DefaultResourceResolverListenerTest extends TestCase
{
    public function setUp()
    {
        $routeMatch = new RouteMatch(array());
        $request    = new HttpRequest();
        $response   = new HttpResponse();
        $mvcEvent   = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response)
            ->setRouteMatch($routeMatch);
        $this->mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->restControllers = array(
            'LaminasCon\V1\Rest\Session\Controller' => 'session_id',
        );
        $this->listener = new DefaultResourceResolverListener($this->restControllers);
    }

    public function createMvcAuthEvent(MvcEvent $mvcEvent)
    {
        $this->authentication = new TestAsset\AuthenticationService();
        $this->authorization  = $this->getMock('Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface');
        return new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);
    }

    public function testBuildResourceStringReturnsFalseIfControllerIsMissing()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $request    = $mvcEvent->getRequest();
        $this->assertFalse($this->listener->buildResourceString($routeMatch, $request));
    }

    public function testBuildResourceStringReturnsControllerActionFormattedStringForNonRestController()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'Foo\Bar\Controller');
        $routeMatch->setParam('action', 'foo');
        $request    = $mvcEvent->getRequest();
        $this->assertEquals('Foo\Bar\Controller::foo', $this->listener->buildResourceString($routeMatch, $request));
    }

    public function testBuildResourceStringReturnsControllerNameAndCollectionIfNoIdentifierAvailable()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'LaminasCon\V1\Rest\Session\Controller');
        $request    = $mvcEvent->getRequest();
        $this->assertEquals(
            'LaminasCon\V1\Rest\Session\Controller::collection',
            $this->listener->buildResourceString($routeMatch, $request)
        );
    }

    public function testBuildResourceStringReturnsControllerNameAndResourceIfIdentifierInRouteMatch()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'LaminasCon\V1\Rest\Session\Controller');
        $routeMatch->setParam('session_id', 'foo');
        $request    = $mvcEvent->getRequest();
        $this->assertEquals(
            'LaminasCon\V1\Rest\Session\Controller::entity',
            $this->listener->buildResourceString($routeMatch, $request)
        );
    }

    public function testBuildResourceStringReturnsControllerNameAndResourceIfIdentifierInQueryString()
    {
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $routeMatch->setParam('controller', 'LaminasCon\V1\Rest\Session\Controller');
        $request    = $mvcEvent->getRequest();
        $request->getQuery()->set('session_id', 'bar');
        $this->assertEquals(
            'LaminasCon\V1\Rest\Session\Controller::entity',
            $this->listener->buildResourceString($routeMatch, $request)
        );
    }
}
