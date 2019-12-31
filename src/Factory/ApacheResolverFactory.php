<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\Authentication\Adapter\Http\ApacheResolver;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ApacheResolverFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ApacheResolver|false
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        if (!$serviceLocator->has('config')) {
            return false;
        }

        $config = $serviceLocator->get('config');

        if (!isset($config['api-tools-mvc-auth']['authentication']['http']['htpasswd'])) {
            return false;
        }

        $htpasswd = $config['api-tools-mvc-auth']['authentication']['http']['htpasswd'];

        return new ApacheResolver($htpasswd);
    }
}
