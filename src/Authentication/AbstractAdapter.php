<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Authentication;

use Laminas\Http\Request;

use function in_array;
use function preg_split;
use function strpos;
use function strtolower;
use function trim;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Authorization token types this adapter can fulfill.
     *
     * @var array
     */
    protected $authorizationTokenTypes = [];

    /**
     * Determine if the incoming request provides either basic or digest
     * credentials
     *
     * @return false|string
     */
    public function getTypeFromRequest(Request $request)
    {
        $request->getHeaders();
        $authorization = $request->getHeader('Authorization');
        if (! $authorization) {
            return false;
        }

        $authorization = trim($authorization->getFieldValue());
        $type          = $this->getTypeFromAuthorizationHeader($authorization);

        if (! in_array($type, $this->authorizationTokenTypes)) {
            return false;
        }

        return $type;
    }

    /**
     * Determine the authentication type from the authorization header contents
     *
     * @param string $header
     * @return false|string
     */
    private function getTypeFromAuthorizationHeader($header)
    {
        // we only support headers in the format: Authorization: xxx yyyyy
        if (strpos($header, ' ') === false) {
            return false;
        }

        [$type, $credential] = preg_split('# #', $header, 2);

        return strtolower($type);
    }
}
