<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\MvcAuth;

use Laminas\Mvc\Router\RouteMatch as V2RouteMatch;
use Laminas\Router\RouteMatch;

trait RouteMatchFactoryTrait
{
    /**
     * Create and return a version-specific RouteMatch instance.
     *
     * @param array $params
     * @return RouteMatch|V2RouteMatch
     */
    public function createRouteMatch(array $params = [])
    {
        $class = class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        return new $class($params);
    }
}
