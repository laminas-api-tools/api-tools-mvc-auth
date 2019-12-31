<?php

namespace LaminasTest\ApiTools\MvcAuth\Identity;

use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Laminas\ApiTools\MvcAuth\Identity\IdentityPlugin;
use Laminas\Mvc\MvcEvent;
use PHPUnit_Framework_TestCase as TestCase;

class IdentityPluginTest extends TestCase
{
    public function setUp()
    {
        $this->event = $event = new MvcEvent();

        $controller = $this->getMockBuilder('Laminas\Mvc\Controller\AbstractController')->getMock();
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
        $this->assertInstanceOf(
            'Laminas\ApiTools\MvcAuth\Identity\GuestIdentity',
            $this->plugin->__invoke()
        );
    }

    public function testInvalidTypeInEventIdentityParamCausesPluginToYieldGuestIdentity()
    {
        $this->event->setParam('Laminas\ApiTools\MvcAuth\Identity', (object) ['foo' => 'bar']);
        $this->assertInstanceOf(
            'Laminas\ApiTools\MvcAuth\Identity\GuestIdentity',
            $this->plugin->__invoke()
        );
    }

    public function testValidIdentityInEventIsReturnedByPlugin()
    {
        $identity = new AuthenticatedIdentity('mwop');
        $this->event->setParam('Laminas\ApiTools\MvcAuth\Identity', $identity);
        $this->assertSame($identity, $this->plugin->__invoke());
    }
}
