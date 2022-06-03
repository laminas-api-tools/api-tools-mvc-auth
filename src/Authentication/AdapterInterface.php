<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Authentication;

use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Request;
use Laminas\Http\Response;

interface AdapterInterface
{
    /**
     * @return array Array of types this adapter can handle.
     */
    public function provides();

    /**
     * Attempt to match a requested authentication type
     * against what the adapter provides.
     *
     * @param string $type
     * @return bool
     */
    public function matches($type);

    /**
     * Attempt to retrieve the authentication type based on the request.
     *
     * Allows an adapter to have custom logic for detecting if a request
     * might be providing credentials it's interested in.
     *
     * @return false|string
     */
    public function getTypeFromRequest(Request $request);

    /**
     * Perform pre-flight authentication operations.
     *
     * Use case would be for providing authentication challenge headers.
     *
     * @return void|Response
     */
    public function preAuth(Request $request, Response $response);

    /**
     * Attempt to authenticate the current request.
     *
     * @return false|IdentityInterface False on failure, IdentityInterface
     *     otherwise
     */
    public function authenticate(Request $request, Response $response, MvcAuthEvent $mvcAuthEvent);
}
