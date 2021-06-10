<?php

namespace LaminasTest\ApiTools\MvcAuth\Authentication;

use Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter;
use Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface;
use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class HttpAdapterTest extends TestCase
{
    public function setUp()
    {
        // authentication service
        $this->authentication = new AuthenticationService(new NonPersistent());

        $this->request  = $request  = new HttpRequest();
        $this->response = $response = new HttpResponse();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request)
            ->setResponse($response);

        $this->event = new MvcAuthEvent(
            $mvcEvent,
            $this->authentication,
            $this->getMockBuilder(AuthorizationInterface::class)->getMock()
        );
    }

    public function testAuthenticateReturnsGuestIdentityIfNoAuthorizationHeaderProvided()
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));

        $adapter = new HttpAdapter($httpAuth, $this->authentication);
        $result  = $adapter->authenticate($this->request, $this->response, $this->event);
        $this->assertInstanceOf(GuestIdentity::class, $result);
    }

    public function testAuthenticateReturnsFalseIfInvalidCredentialsProvidedInAuthorizationHeader()
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));

        $adapter = new HttpAdapter($httpAuth, $this->authentication);

        $this->request->getHeaders()->addHeaderLine('Authorization', 'Bearer BOGUS TOKEN');

        $this->assertFalse($adapter->authenticate($this->request, $this->response, $this->event));
    }

    public function testAuthenticateReturnsAuthenticatedIdentityIfValidCredentialsProvidedInAuthorizationHeader()
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));

        $adapter = new HttpAdapter($httpAuth, $this->authentication);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic dXNlcjp1c2Vy');
        $result = $adapter->authenticate($this->request, $this->response, $this->event);
        $this->assertInstanceOf(AuthenticatedIdentity::class, $result);
    }
}
