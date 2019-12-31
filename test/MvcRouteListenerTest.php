<?php

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\ApiTools\MvcAuth\MvcRouteListener;
use Laminas\EventManager\EventManager;
use PHPUnit_Framework_TestCase as TestCase;

class MvcRouteListenerTest extends TestCase
{
    public function setUp()
    {
        $this->events = new EventManager;
        $this->auth   = $this
            ->getMockBuilder('Laminas\Authentication\AuthenticationService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event  = $this
            ->getMockBuilder('Laminas\ApiTools\MvcAuth\MvcAuthEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MvcRouteListener(
            $this->event,
            $this->events,
            $this->auth
        );
    }

    public function assertListenerAtPriority($priority, $expectedCallback, $listeners, $message = '')
    {
        $found = false;
        foreach ($listeners as $listener) {
            $this->assertInstanceOf('Laminas\Stdlib\CallbackHandler', $listener);
            if ($listener->getMetadatum('priority') !== $priority) {
                continue;
            }

            if ($listener->getCallback() === $expectedCallback) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, $message);
    }

    public function testRegistersAuthenticationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -50,
            [$this->listener, 'authentication'],
            $this->events->getListeners('route')
        );
    }

    public function testRegistersPostAuthenticationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -51,
            [$this->listener, 'authenticationPost'],
            $this->events->getListeners('route')
        );
    }

    public function testRegistersAuthorizationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -600,
            [$this->listener, 'authorization'],
            $this->events->getListeners('route')
        );
    }

    public function testRegistersPostAuthorizationListenerOnExpectedPriority()
    {
        $this->events->attach($this->listener);
        $this->assertListenerAtPriority(
            -601,
            [$this->listener, 'authorizationPost'],
            $this->events->getListeners('route')
        );
    }
}
