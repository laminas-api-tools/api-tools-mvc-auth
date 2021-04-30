<?php

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\ApiTools\MvcAuth\MvcRouteListener;
use Laminas\Authentication\AuthenticationService;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MvcRouteListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    /** @var AuthenticationService&MockObject */
    private $auth;

    /** @var MvcAuthEvent&MockObject */
    private $event;

    /** @var EventManager */
    private $events;

    /** @var MvcRouteListener */
    private $listener;

    public function setUp(): void
    {
        $this->events = new EventManager();
        $this->auth   = $this
            ->getMockBuilder(AuthenticationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->event  = $this
            ->getMockBuilder(MvcAuthEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MvcRouteListener(
            $this->event,
            $this->events,
            $this->auth
        );
    }

    public function testRegistersAuthenticationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authentication'],
            -50,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }

    public function testRegistersPostAuthenticationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authenticationPost'],
            -51,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }

    public function testRegistersAuthorizationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authorization'],
            -600,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }

    public function testRegistersPostAuthorizationListenerOnExpectedPriority()
    {
        $this->listener->attach($this->events);
        $this->assertListenerAtPriority(
            [$this->listener, 'authorizationPost'],
            -601,
            MvcEvent::EVENT_ROUTE,
            $this->events
        );
    }
}
