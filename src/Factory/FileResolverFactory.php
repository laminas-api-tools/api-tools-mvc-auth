<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\Authentication\Adapter\Http\FileResolver;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FileResolverFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return FileResolver|false
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        if (!$serviceLocator->has('config')) {
            return false;
        }

        $config = $serviceLocator->get('config');

        if (!isset($config['api-tools-mvc-auth']['authentication']['http']['htdigest'])) {
            return false;
        }

        $htdigest = $config['api-tools-mvc-auth']['authentication']['http']['htdigest'];

        return new FileResolver($htdigest);
    }
}
