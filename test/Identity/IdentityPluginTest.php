<?php

namespace LaminasTest\ApiTools\MvcAuth\Identity;

use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\Identity\IdentityPlugin;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class IdentityPluginTest extends TestCase
{
    public function setUp(): void
    {
        $this->event = $event = new MvcEvent();

        $controller = $this->getMockBuilder(AbstractController::class)->getMock();
        $controller->expects($this->any())
            ->method('getEvent')
            ->will($this->returnCallback(function () use ($event) {
                return $event;
            }));

        $this->plugin = new IdentityPlugin();
        $this->plugin->setController($controller);
    }

    public function testMissingIdentityParamInEventCausesPluginToYieldGuestIdentity()
    {
        $this->assertInstanceOf(GuestIdentity::class, $this->plugin->__invoke());
    }

    public function testInvalidTypeInEventIdentityParamCausesPluginToYieldGuestIdentity()
    {
        $this->event->setParam('Laminas\ApiTools\MvcAuth\Identity', (object) ['foo' => 'bar']);
        $this->assertInstanceOf(GuestIdentity::class, $this->plugin->__invoke());
    }

    public function testValidIdentityInEventIsReturnedByPlugin()
    {
        $identity = new AuthenticatedIdentity('mwop');
        $this->event->setParam('Laminas\ApiTools\MvcAuth\Identity', $identity);
        $this->assertSame($identity, $this->plugin->__invoke());
    }
}
