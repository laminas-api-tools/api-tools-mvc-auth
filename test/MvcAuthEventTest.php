<?php

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Mvc\MvcEvent;
use Laminas\Permissions\Acl\Acl;
use PHPUnit\Framework\TestCase;

class MvcAuthEventTest extends TestCase
{
    /** @var MvcAuthEvent */
    protected $mvcAuthEvent;

    public function setUp(): void
    {
        $mvcEvent           = new MvcEvent();
        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent, new AuthenticationService(), new Acl());
    }

    public function testGetAuthenticationService(): void
    {
        $this->assertInstanceOf(AuthenticationService::class, $this->mvcAuthEvent->getAuthenticationService());
    }

    public function testHasAuthenticationResult(): void
    {
        $this->assertFalse($this->mvcAuthEvent->hasAuthenticationResult());
        $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'));
        $this->assertTrue($this->mvcAuthEvent->hasAuthenticationResult());
    }

    public function testSetAuthenticationResult(): void
    {
        $this->assertSame(
            $this->mvcAuthEvent,
            $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'))
        );
    }

    public function testGetAuthenticationResult(): void
    {
        $this->mvcAuthEvent->setAuthenticationResult(new Result('success', 'foobar'));
        $this->assertInstanceOf(Result::class, $this->mvcAuthEvent->getAuthenticationResult());
    }

    public function testGetAuthorizationService(): void
    {
        $this->assertInstanceOf(Acl::class, $this->mvcAuthEvent->getAuthorizationService());
    }

    public function testGetMvcEvent(): void
    {
        $this->assertInstanceOf(MvcEvent::class, $this->mvcAuthEvent->getMvcEvent());
    }

    public function testSetIdentity(): void
    {
        $this->assertSame($this->mvcAuthEvent, $this->mvcAuthEvent->setIdentity(new GuestIdentity()));
    }

    public function testGetIdentity(): void
    {
        $this->mvcAuthEvent->setIdentity($i = new GuestIdentity());
        $this->assertSame($i, $this->mvcAuthEvent->getIdentity());
    }

    public function testResourceStringIsNullByDefault(): void
    {
        $this->assertNull($this->mvcAuthEvent->getResource());
    }

    /**
     * @depends testResourceStringIsNullByDefault
     */
    public function testResourceStringIsMutable(): void
    {
        $this->mvcAuthEvent->setResource('foo');
        $this->assertEquals('foo', $this->mvcAuthEvent->getResource());
    }

    public function testAuthorizedFlagIsFalseByDefault(): void
    {
        $this->assertFalse($this->mvcAuthEvent->isAuthorized());
    }

    /**
     * @depends testAuthorizedFlagIsFalseByDefault
     */
    public function testAuthorizedFlagIsMutable(): void
    {
        $this->mvcAuthEvent->setIsAuthorized(true);
        $this->assertTrue($this->mvcAuthEvent->isAuthorized());
    }
}
