<?php

namespace LaminasTest\ApiTools\MvcAuth\Authorization;

use Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface;
use Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Response;
use LaminasTest\ApiTools\MvcAuth\TestAsset;
use PHPUnit\Framework\TestCase;

class DefaultAuthorizationPostListenerTest extends TestCase
{
    /** @var DefaultAuthorizationPostListener */
    private $listener;

    /** @var MvcAuthEvent */
    private $mvcAuthEvent;

    public function setUp()
    {
        $response = new HttpResponse();
        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse($response);
        $this->mvcAuthEvent = $this->createMvcAuthEvent($mvcEvent);

        $this->listener = new DefaultAuthorizationPostListener();
    }

    public function createMvcAuthEvent(MvcEvent $mvcEvent): MvcAuthEvent
    {
        $this->authentication = new TestAsset\AuthenticationService();
        $this->authorization  = $this->getMockBuilder(AuthorizationInterface::class)->getMock();
        return new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);
    }

    public function testReturnsNullWhenEventIsAuthorized()
    {
        $listener = $this->listener;
        $this->mvcAuthEvent->setIsAuthorized(true);
        $this->assertNull($listener($this->mvcAuthEvent));
    }

    public function testResetsResponseStatusTo200WhenEventIsAuthorized()
    {
        $listener = $this->listener;
        $response = $this->mvcAuthEvent->getMvcEvent()->getResponse();
        $response->setStatusCode(401);
        $this->mvcAuthEvent->setIsAuthorized(true);
        $listener($this->mvcAuthEvent);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReturnsComposedEventResponseWhenNotAuthorizedButNotAnHttpResponse()
    {
        $listener = $this->listener;
        $response = new Response();
        $this->mvcAuthEvent->getMvcEvent()->setResponse($response);
        $this->assertSame($response, $listener($this->mvcAuthEvent));
    }

    public function testReturns403ResponseWhenNotAuthorizedAndHttpResponseComposed()
    {
        $listener = $this->listener;
        $response = $this->mvcAuthEvent->getMvcEvent()->getResponse();
        $this->assertSame($response, $listener($this->mvcAuthEvent));
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Forbidden', $response->getReasonPhrase());
    }
}
