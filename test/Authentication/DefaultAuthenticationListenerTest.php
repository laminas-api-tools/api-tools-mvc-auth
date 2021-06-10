<?php // phpcs:disable Generic.Files.LineLength.TooLong

namespace LaminasTest\ApiTools\MvcAuth\Authentication;

use Laminas\ApiTools\MvcAuth\Authentication\AdapterInterface;
use Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface;
use Laminas\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Config\Config;
use Laminas\Http\Header\HeaderInterface as HttpHeaderInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Request;
use LaminasTest\ApiTools\MvcAuth\RouteMatchFactoryTrait;
use OAuth2\Request as OAuth2Request;
use OAuth2\Server as OAuth2Server;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_merge;

class DefaultAuthenticationListenerTest extends TestCase
{
    use RouteMatchFactoryTrait;

    /** @var HttpRequest */
    protected $request;

    /** @var HttpResponse */
    protected $response;

    /** @var AuthenticationService */
    protected $authentication;

    /** @var AuthorizationInterface&MockObject */
    protected $authorization;

    /** @var array */
    protected $restControllers = [];

    /** @var DefaultAuthenticationListener */
    protected $listener;

    /** @var MvcAuthEvent */
    protected $mvcAuthEvent;

    /** @var Config */
    protected $configuration;

    public function setUp(): void
    {
        // authentication service
        $this->authentication = new AuthenticationService(new NonPersistent());

        // authorization service
        $this->authorization = $this->getMockBuilder(AuthorizationInterface::class)->getMock();

        // event for mvc and mvc-auth
        $this->request  = new HttpRequest();
        $this->response = new HttpResponse();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($this->request)
            ->setResponse($this->response);

        $this->mvcAuthEvent = new MvcAuthEvent($mvcEvent, $this->authentication, $this->authorization);
        $this->listener     = new DefaultAuthenticationListener();
    }

    public function testInvokeReturnsEarlyWhenNotHttpRequest(): void
    {
        $this->mvcAuthEvent->getMvcEvent()->setRequest(new Request());
        $this->assertNull($this->listener->__invoke($this->mvcAuthEvent));
    }

    public function testInvokeForBasicAuthAddsAuthorizationHeader(): void
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->listener->__invoke($this->mvcAuthEvent);

        $authHeaders = $this->response->getHeaders()->get('WWW-Authenticate');
        $authHeader  = $authHeaders[0];
        $this->assertInstanceOf(HttpHeaderInterface::class, $authHeader);
        $this->assertEquals('Basic realm="My Web Site"', $authHeader->getFieldValue());
    }

    /** @psalm-return array{identity: IdentityInterface, mvc_event: MvcEvent} */
    public function testInvokeForBasicAuthSetsIdentityWhenValid(): array
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic dXNlcjp1c2Vy');
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(AuthenticatedIdentity::class, $identity);
        $this->assertEquals('user', $identity->getRoleId());
        return ['identity' => $identity, 'mvc_event' => $this->mvcAuthEvent->getMvcEvent()];
    }

    /** @psalm-return array{identity: IdentityInterface, mvc_event: MvcEvent} */
    public function testInvokeForBasicAuthSetsGuestIdentityWhenValid(): array
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic xxxxxxxxx');
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(GuestIdentity::class, $identity);
        $this->assertEquals('guest', $identity->getRoleId());
        return ['identity' => $identity, 'mvc_event' => $this->mvcAuthEvent->getMvcEvent()];
    }

    public function testInvokeForBasicAuthHasNoIdentityWhenNotValid(): void
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->request->getHeaders()->addHeaderLine('Authorization: Basic xxxxxxxxx');
        $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertNull($this->mvcAuthEvent->getIdentity());
    }

    public function testInvokeForDigestAuthAddsAuthorizationHeader(): void
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'digest',
            'realm'          => 'User Area',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setDigestResolver(new HttpAuth\FileResolver(__DIR__ . '/../TestAsset/htdigest'));
        $this->listener->setHttpAdapter($httpAuth);

        $this->listener->__invoke($this->mvcAuthEvent);

        $authHeaders = $this->response->getHeaders()->get('WWW-Authenticate');
        $authHeader  = $authHeaders[0];
        $this->assertInstanceOf(HttpHeaderInterface::class, $authHeader);
        $this->assertMatchesRegularExpression(
            '#^Digest realm="User Area", domain="/", '
            . 'nonce="[a-f0-9]{32}", '
            . 'opaque="cbf8b7892feb4d4aaacecc4e4fb12f83", '
            . 'algorithm="MD5", '
            . 'qop="auth"$#',
            $authHeader->getFieldValue()
        );
    }

    /**
     * @param array $params
     * @depends testInvokeForBasicAuthSetsIdentityWhenValid
     * @psalm-param array{identity: array, mvc_event: MvcEvent} $params
     */
    public function testListenerInjectsDiscoveredIdentityIntoMvcEvent(array $params): void
    {
        $identity = $params['identity'];
        $mvcEvent = $params['mvc_event'];

        $received = $mvcEvent->getParam('Laminas\ApiTools\MvcAuth\Identity', false);
        $this->assertSame($identity, $received);
    }

    /**
     * @param array $params
     * @depends testInvokeForBasicAuthSetsGuestIdentityWhenValid
     * @psalm-param array{identity: array, mvc_event: MvcEvent} $params
     */
    public function testListenerInjectsGuestIdentityIntoMvcEvent(array $params): void
    {
        $identity = $params['identity'];
        $mvcEvent = $params['mvc_event'];

        $received = $mvcEvent->getParam('Laminas\ApiTools\MvcAuth\Identity', false);
        $this->assertSame($identity, $received);
    }

    /**
     * @group 23
     */
    public function testListenerPullsDigestUsernameFromAuthenticationIdentityWhenCreatingAuthenticatedIdentityInstance(): void
    {
        $httpAuth       = $this->getMockBuilder(HttpAuth::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultIdentity = new AuthenticationResult(AuthenticationResult::SUCCESS, [
            'username' => 'user',
            'realm'    => 'User Area',
        ]);
        $httpAuth->expects($this->any())
            ->method('getBasicResolver')
            ->will($this->returnValue(false));
        $httpAuth->expects($this->any())
            ->method('getDigestResolver')
            ->will($this->returnValue(true));
        $httpAuth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($resultIdentity));

        $this->listener->setHttpAdapter($httpAuth);
        $this->request->getHeaders()->addHeaderLine(
            'Authorization: Digest username="user", '
            . 'realm="User Area", '
            . 'nonce="AB10BC99", '
            . 'uri="/", '
            . 'qop="auth", '
            . 'nc="AB10BC99", '
            . 'cnonce="AB10BC99", '
            . 'response="b19adb0300f4bd21baef59b0b4814898", '
            . 'opaque=""'
        );

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(AuthenticatedIdentity::class, $identity);
        $this->assertEquals('user', $identity->getRoleId());
    }

    public function testBearerTypeProxiesOAuthServer(): void
    {
        $token = [
            'user_id' => 'test',
        ];

        $this->setupMockOAuth2Server($token);
        $this->request->getHeaders()->addHeaderLine('Authorization', 'Bearer TOKEN');

        $identity = $this->listener->__invoke($this->mvcAuthEvent);

        $this->assertIdentityMatchesToken($token, $identity);
    }

    public function testQueryAccessTokenProxiesOAuthServer(): void
    {
        $token = [
            'user_id' => 'test',
        ];

        $this->setupMockOAuth2Server($token);
        $this->request->getQuery()->set('access_token', 'TOKEN');

        $identity = $this->listener->__invoke($this->mvcAuthEvent);

        $this->assertIdentityMatchesToken($token, $identity);
    }

    /** @psalm-return array<array-key, array{0: string}> */
    public function requestMethodsWithRequestBodies(): array
    {
        return [
            ['DELETE'],
            ['PATCH'],
            ['POST'],
            ['PUT'],
        ];
    }

    /**
     * @dataProvider requestMethodsWithRequestBodies
     */
    public function testBodyAccessTokenProxiesOAuthServer(string $method): void
    {
        $token = [
            'user_id' => 'test',
        ];

        $this->setupMockOAuth2Server($token);
        $this->request->setMethod($method);
        $this->request->getHeaders()->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded');
        $this->request->getPost()->set('access_token', 'TOKEN');

        $identity = $this->listener->__invoke($this->mvcAuthEvent);

        $this->assertIdentityMatchesToken($token, $identity);
    }

    protected function setupMockOAuth2Server(array $token): void
    {
        $server = $this->getMockBuilder(OAuth2Server::class)
            ->disableOriginalConstructor()
            ->getMock();

        $server->expects($this->atLeastOnce())
            ->method('getAccessTokenData')
            ->will($this->returnValue($token));

        $this->listener->setOauth2Server($server);
    }

    public static function assertIdentityMatchesToken(
        array $token,
        IdentityInterface $identity,
        string $message = ''
    ): void {
        self::assertInstanceOf(AuthenticatedIdentity::class, $identity, $message);
        self::assertEquals($token['user_id'], $identity->getRoleId());
        self::assertEquals($token, $identity->getAuthenticationIdentity());
    }

    public function setupHttpBasicAuth(): void
    {
        $httpAuth = new HttpAuth([
            'accept_schemes' => 'basic',
            'realm'          => 'My Web Site',
            'digest_domains' => '/',
            'nonce_timeout'  => 3600,
        ]);
        $httpAuth->setBasicResolver(new HttpAuth\ApacheResolver(__DIR__ . '/../TestAsset/htpasswd'));
        $this->listener->setHttpAdapter($httpAuth);
    }

    public function setupHttpDigestAuth(): void
    {
        $httpAuth       = $this->getMockBuilder(HttpAuth::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultIdentity = new AuthenticationResult(AuthenticationResult::SUCCESS, [
            'username' => 'user',
            'realm'    => 'User Area',
        ]);
        $httpAuth->expects($this->any())
            ->method('getDigestResolver')
            ->will($this->returnValue(true));
        $httpAuth->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($resultIdentity));

        $this->listener->setHttpAdapter($httpAuth);
    }

    /**
     * @psalm-return array<string, array{
     *     0: string,
     *     1: string,
     *     2: callable():HttpRequest
     * }>
     */
    public function mappedAuthenticationControllers(): array
    {
        return [
            'Foo\V2' => [
                'Foo\V2\Rest\Status\StatusController',
                'oauth2',
                function () {
                    $request = new HttpRequest();
                    $request->getHeaders()->addHeaderLine('Authorization: Bearer TOKEN');
                    return $request;
                },
            ],
            'Bar\V1' => [
                'Bar\V1\Rpc\Ping\PingController',
                'basic',
                function () {
                    $request = new HttpRequest();
                    $request->getHeaders()->addHeaderLine('Authorization: Basic dXNlcjp1c2Vy');
                    return $request;
                },
            ],
            'Baz\V3' => [
                'Baz\V3\Rest\User\UserController',
                'digest',
                function () {
                    $request = new HttpRequest();
                    $request->getHeaders()->addHeaderLine(
                        'Authorization: Digest username="user", '
                        . 'realm="User Area", '
                        . 'nonce="AB10BC99", '
                        . 'uri="/", '
                        . 'qop="auth", '
                        . 'nc="AB10BC99", '
                        . 'cnonce="AB10BC99", '
                        . 'response="b19adb0300f4bd21baef59b0b4814898", '
                        . 'opaque=""'
                    );
                    return $request;
                },
            ],
        ];
    }

    public function setupMappedAuthenticatingListener(
        string $authType,
        string $controller,
        HttpRequest $request
    ): void {
        switch ($authType) {
            case 'basic':
                $this->setupHttpBasicAuth();
                break;
            case 'digest':
                $this->setupHttpDigestAuth();
                break;
            case 'oauth2':
                $this->setupMockOAuth2Server([
                    'user_id' => 'test',
                ]);
                break;
        }

        $map = [
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        ];
        $this->listener->setAuthMap($map);
        $routeMatch = $this->createRouteMatch(['controller' => $controller]);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     * @psalm-param callable():HttpRequest $requestProvider
     */
    public function testAuthenticationUsesMapByToChooseAuthenticationMethod(
        string $controller,
        string $authType,
        callable $requestProvider
    ): void {
        $this->setupMappedAuthenticatingListener($authType, $controller, $requestProvider());
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(AuthenticatedIdentity::class, $identity);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     * @psalm-param callable():HttpRequest $requestProvider
     */
    public function testGuestIdentityIsReturnedWhenNoAuthSchemesArePresent(
        string $controller,
        string $authType,
        callable $requestProvider
    ): void {
        $map = [
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        ];
        $this->listener->setAuthMap($map);
        $routeMatch = $this->createRouteMatch(['controller' => $controller]);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($requestProvider())
            ->setRouteMatch($routeMatch);
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(GuestIdentity::class, $identity);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     * @psalm-param callable():HttpRequest $requestProvider
     */
    public function testUsesDefaultAuthenticationWhenNoAuthMapIsPresent(
        string $controller,
        string $authType,
        callable $requestProvider
    ): void {
        switch ($authType) {
            case 'basic':
                $this->setupHttpBasicAuth();
                break;
            case 'digest':
                $this->setupHttpDigestAuth();
                break;
            case 'oauth2':
                $this->setupMockOAuth2Server([
                    'user_id' => 'test',
                ]);
                break;
        }

        $routeMatch = $this->createRouteMatch(['controller' => $controller]);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($requestProvider())
            ->setRouteMatch($routeMatch);
        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(AuthenticatedIdentity::class, $identity);
    }

    /**
     * @dataProvider mappedAuthenticationControllers
     * @group 55
     * @psalm-param callable():HttpRequest $requestProvider
     */
    public function testDoesNotPerformAuthenticationWhenNoAuthMapPresentAndMultipleAuthSchemesAreDefined(
        string $controller,
        string $authType,
        callable $requestProvider
    ): void {
        $this->setupHttpBasicAuth();
        // Minimal OAuth2 server mock, as we are not expecting any method calls
        $server = $this->getMockBuilder(OAuth2Server::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setOauth2Server($server);

        $routeMatch = $this->createRouteMatch(['controller' => $controller]);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($requestProvider())
            ->setRouteMatch($routeMatch);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(GuestIdentity::class, $identity);
    }

    /**
     * @group 55
     */
    public function testDoesNotPerformAuthenticationWhenMatchedControllerHasNoAuthMapEntryAndAuthSchemesAreDefined(): void
    {
        // Minimal HTTP adapter mock, as we are not expecting any method calls
        $httpAuth = $this->getMockBuilder(HttpAuth::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setHttpAdapter($httpAuth);

        // Minimal OAuth2 server mock, as we are not expecting any method calls
        $server = $this->getMockBuilder(OAuth2Server::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setOauth2Server($server);

        $map = [
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        ];
        $this->listener->setAuthMap($map);

        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Authorization: Bearer TOKEN');

        $routeMatch = $this->createRouteMatch(['controller' => 'FooBarBaz\V4\Rest\Test\TestController']);

        $mvcEvent = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(GuestIdentity::class, $identity);
    }

    /**
     * @group 55
     */
    public function testDoesNotPerformAuthenticationWhenMatchedControllerHasAuthMapEntryNotInDefinedAuthSchemes(): void
    {
        // Minimal HTTP adapter mock, as we are not expecting any method calls
        $httpAuth = $this->getMockBuilder(HttpAuth::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener->setHttpAdapter($httpAuth);

        // No OAuth2 server, intentionally

        $map = [
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        ];
        $this->listener->setAuthMap($map);

        $request = new HttpRequest();
        $request->getHeaders()->addHeaderLine('Authorization: Bearer TOKEN');

        $routeMatch = $this->createRouteMatch(['controller' => 'Foo\V2\Rest\Test\TestController']);

        $mvcEvent = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(GuestIdentity::class, $identity);
    }

    public function testAllowsAttachingAdapters(): void
    {
        $types   = ['foo'];
        $adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $this->listener->attach($adapter);
    }

    public function testCanRetrieveSupportedAuthenticationTypes(): void
    {
        $types   = ['foo'];
        $adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $this->listener->attach($adapter);
        $this->assertEquals($types, $this->listener->getAuthenticationTypes());
    }

    public function testAdapterPreAuthIsTriggeredWhenNoTypeMatchedInRequest(): void
    {
        $map = [
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        ];
        $this->listener->setAuthMap($map);
        $request    = new HttpRequest();
        $routeMatch = $this->createRouteMatch(['controller' => 'Foo\V1\Rest\Test\TestController']);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $types   = ['foo'];
        $adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter->expects($this->once())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue(false));
        $adapter->expects($this->once())
            ->method('preAuth')
            ->with($this->equalTo($request), $this->equalTo($this->response))
            ->will($this->returnValue(null));

        $this->listener->attach($adapter);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertInstanceOf(GuestIdentity::class, $identity);
    }

    public function testMatchedAdapterIsAuthenticatedAgainst(): void
    {
        $map = [
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        ];
        $this->listener->setAuthMap($map);
        $request    = new HttpRequest();
        $routeMatch = $this->createRouteMatch(['controller' => 'Foo\V2\Rest\Test\TestController']);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $types   = ['oauth2'];
        $adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue('oauth2'));
        $adapter->expects($this->any())
            ->method('matches')
            ->with($this->equalTo('oauth2'))
            ->will($this->returnValue(true));
        $expected = $this->getMockBuilder(AuthenticatedIdentity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($request), $this->equalTo($this->response))
            ->will($this->returnValue($expected));
        $this->listener->attach($adapter);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertSame($expected, $identity);
    }

    public function testFirstAdapterProvidingTypeIsAuthenticatedAgainst(): void
    {
        $map = [
            'Foo\V2' => 'oauth2',
            'Bar\V1' => 'basic',
            'Baz\V3' => 'digest',
        ];
        $this->listener->setAuthMap($map);
        $request    = new HttpRequest();
        $routeMatch = $this->createRouteMatch(['controller' => 'Foo\V2\Rest\Test\TestController']);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $types    = ['oauth2'];
        $adapter1 = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter1->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter1->expects($this->any())
            ->method('matches')
            ->with($this->equalTo('oauth2'))
            ->will($this->returnValue(true));
        $adapter1->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue('oauth2'));
        $expected = $this->getMockBuilder(AuthenticatedIdentity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter1->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($request), $this->equalTo($this->response))
            ->will($this->returnValue($expected));

        $adapter2 = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter2->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter2->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue('oauth2'));

        $this->listener->attach($adapter1);
        $this->listener->attach($adapter2);

        $identity = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertSame($expected, $identity);
    }

    public function testListsProvidedNonAdapterAuthenticationTypes(): void
    {
        $types = ['foo'];
        $this->listener->addAuthenticationTypes($types);
        $this->assertEquals($types, $this->listener->getAuthenticationTypes());
    }

    public function testListsCombinedAuthenticationTypes(): void
    {
        $types       = ['foo'];
        $customTypes = ['bar'];
        $this->listener->addAuthenticationTypes($customTypes);

        $adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $this->listener->attach($adapter);

        // Order of merge matters, unfortunately
        $this->assertEquals(array_merge($customTypes, $types), $this->listener->getAuthenticationTypes());
    }

    public function testOauth2RequestIncludesHeaders(): void
    {
        $this->request->getHeaders()->addHeaderLine('Authorization', 'Bearer TOKEN');

        $server = $this->getMockBuilder(OAuth2Server::class)
            ->disableOriginalConstructor()
            ->getMock();

        $server->expects($this->atLeastOnce())
            ->method('getAccessTokenData')
            ->with($this->callback(function (OAuth2Request $request) {
                return $request->headers('Authorization') === 'Bearer TOKEN';
            }))
            ->willReturn(['user_id' => 'TOKEN']);

        $this->listener->attach(new OAuth2Adapter($server));
        $this->listener->__invoke($this->mvcAuthEvent);
    }

    /**
     * @group 83
     */
    public function testAllowsAdaptersToReturnResponsesAndReturnsThemDirectly(): void
    {
        $map = [
            'Foo\V2' => 'custom',
        ];
        $this->listener->setAuthMap($map);
        $request    = new HttpRequest();
        $routeMatch = $this->createRouteMatch(['controller' => 'Foo\V2\Rest\Test\TestController']);
        $mvcEvent   = $this->mvcAuthEvent->getMvcEvent();
        $mvcEvent
            ->setRequest($request)
            ->setRouteMatch($routeMatch);

        $types   = ['custom'];
        $adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->atLeastOnce())
            ->method('provides')
            ->will($this->returnValue($types));
        $adapter->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($this->equalTo($request))
            ->will($this->returnValue('custom'));
        $adapter->expects($this->any())
            ->method('matches')
            ->with($this->equalTo('custom'))
            ->will($this->returnValue(true));

        $response = new HttpResponse();
        $response->setStatusCode(401);

        $adapter->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($request), $this->equalTo($this->response))
            ->will($this->returnValue($response));
        $this->listener->attach($adapter);

        $result = $this->listener->__invoke($this->mvcAuthEvent);
        $this->assertSame($response, $result);
    }
}
