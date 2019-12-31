<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Authentication;

use Laminas\ApiTools\MvcAuth\Identity;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\Http\Request as HttpRequest;
use OAuth2\Request as OAuth2Request;
use OAuth2\Server as OAuth2Server;

class DefaultAuthenticationListener
{
    /**
     * @var HttpAuth
     */
    protected $httpAdapter;

    /**
     * @var OAuth2Server
     */
    protected $oauth2Server;

    /**
     * Set the HTTP authentication adapter
     *
     * @param HttpAuth $httpAdapter
     * @return self
     */
    public function setHttpAdapter(HttpAuth $httpAdapter)
    {
        $this->httpAdapter = $httpAdapter;
        return $this;
    }

    /**
     * Set the OAuth2 server
     *
     * @param  OAuth2Server $oauth2Server
     * @return self
     */
    public function setOauth2Server(OAuth2Server $oauth2Server)
    {
        $this->oauth2Server = $oauth2Server;
        return $this;
    }

    /**
     * Listen to the authentication event
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return mixed
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request  = $mvcEvent->getRequest();
        $response = $mvcEvent->getResponse();

        if (!$request instanceof HttpRequest
            || $request->isOptions()
        ) {
            return;
        }

        $authHeader = $request->getHeader('Authorization');
        if ($this->httpAdapter instanceof HttpAuth) {
            $this->httpAdapter->setRequest($request);
            $this->httpAdapter->setResponse($response);
        }

        if ($authHeader === false) {
            if ($this->httpAdapter instanceof HttpAuth) {
                $this->httpAdapter->challengeClient();
            }
            return;
        }

        $headerContent = trim($authHeader->getFieldValue());

        // we only support headers in the format: Authorization: xxx yyyyy
        if (strpos($headerContent, ' ') === false) {
            return;
        }

        list($type, $credential) = preg_split('# #', $headerContent, 2);

        switch (strtolower($type)) {
            case 'basic':
            case 'digest':

                if (!$this->httpAdapter instanceof HttpAuth) {
                    return;
                }

                $auth   = $mvcAuthEvent->getAuthenticationService();
                $result = $auth->authenticate($this->httpAdapter);
                $mvcAuthEvent->setAuthenticationResult($result);

                if ($result->isValid()) {
                    $identity = new Identity\AuthenticatedIdentity($result->getIdentity());
                    $identity->setName($result->getIdentity());
                    return $identity;
                }

                $identity = new Identity\GuestIdentity();
                return $identity;

            case 'oauth2':
            case 'bearer':

                if (!$this->oauth2Server instanceof OAuth2Server) {
                    return;
                }

                $content       = $request->getContent();
                $oauth2request = new OAuth2Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER, $content);

                if ($this->oauth2Server->verifyResourceRequest($oauth2request)) {
                    $token    = $this->oauth2Server->getAccessTokenData($oauth2request);
                    $identity = new Identity\AuthenticatedIdentity($token['user_id']);
                    $identity->setName($token['user_id']);
                    return $identity;
                }

                $identity = new Identity\GuestIdentity();
                return $identity;

            case 'token':
                throw new \Exception('api-tools-mvc-auth has not yet implemented a "token" authentication adapter');
        }
    }
}
