<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth\Authentication;

use ArrayIterator;
use Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\ApiTools\MvcAuth\Identity\GuestIdentity;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use OAuth2\Request as OAuth2Request;
use OAuth2\Response as OAuth2Response;
use OAuth2\Server as OAuth2Server;
use PHPUnit\Framework\TestCase;

class OAuth2AdapterTest extends TestCase
{
    public function setUp()
    {
        $this->oauthServer = $this->getMockBuilder(OAuth2Server::class)->getMock();
        $this->adapter = new OAuth2Adapter($this->oauthServer);
    }

    /**
     * @group 83
     */
    public function testReturns401ResponseWhenErrorOccursDuringValidation()
    {
        $oauth2Response = $this->getMockBuilder(OAuth2Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(401);
        $oauth2Response
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('error'))
            ->willReturn('invalid');
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn([]);

        $this->oauthServer
            ->expects($this->once())
            ->method('getAccessTokenData')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(null);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder(MvcAuthEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf(HttpResponse::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    /**
     * @group 83
     */
    public function testReturns403ResponseWhenInvalidScopeDetected()
    {
        $oauth2Response = $this->getMockBuilder(OAuth2Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(403);
        $oauth2Response
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('error'))
            ->willReturn('invalid');
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn([]);

        $this->oauthServer
            ->expects($this->once())
            ->method('getAccessTokenData')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(null);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder(MvcAuthEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf(HttpResponse::class, $result);
        $this->assertEquals(403, $result->getStatusCode());
    }

    /**
     * @group 83
     */
    public function testReturnsGuestIdentityIfOAuth2ResponseIsNotAnError()
    {
        $oauth2Response = $this->getMockBuilder(OAuth2Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn([]);

        $this->oauthServer
            ->expects($this->once())
            ->method('getAccessTokenData')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(null);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder(MvcAuthEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf(GuestIdentity::class, $result);
    }

    /**
     * @group 83
     */
    public function testErrorResponseIncludesOAuth2ResponseHeaders()
    {
        $expectedHeaders = [
            'WWW-Authenticate' => 'Bearer realm="example.com", '
            . 'scope="user", '
            . 'error="unauthorized", '
            . 'error_description="User has insufficient privileges"',
        ];
        $oauth2Response = $this->getMockBuilder(OAuth2Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $oauth2Response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(401);
        $oauth2Response
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('error'))
            ->willReturn('invalid');
        $oauth2Response
            ->expects($this->once())
            ->method('getHttpHeaders')
            ->willReturn($expectedHeaders);

        $this->oauthServer
            ->expects($this->once())
            ->method('getAccessTokenData')
            ->with($this->callback(function ($subject) {
                return ($subject instanceof OAuth2Request);
            }))
            ->willReturn(null);

        $this->oauthServer
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($oauth2Response);

        $mvcAuthEvent = $this->getMockBuilder(MvcAuthEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->adapter->authenticate(new HttpRequest, new HttpResponse, $mvcAuthEvent);
        $this->assertInstanceOf(HttpResponse::class, $result);

        $headers = $result->getHeaders();
        foreach ($expectedHeaders as $name => $value) {
            $this->assertTrue($headers->has($name));
            $header = $headers->get($name);
            if ($header instanceof ArrayIterator) {
                $found = false;
                foreach ($header as $instance) {
                    if ($instance->getFieldValue() == $value) {
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, 'Expected header value not found');
                continue;
            }

            $this->assertEquals($value, $header->getFieldValue());
        }
    }
}
