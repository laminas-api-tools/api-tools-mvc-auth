<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */
namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ServiceManager\DelegatorFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class AuthenticationAdapterDelegatorFactory implements DelegatorFactoryInterface
{
    public function createDelegatorWithName(
        ServiceLocatorInterface $services,
        $name,
        $requestedName,
        $callback
    ) {
        $listener = $callback();

        $config = $services->get('Config');
        if (! isset($config['api-tools-mvc-auth']['authentication']['adapters'])
            || ! is_array($config['api-tools-mvc-auth']['authentication']['adapters'])
        ) {
            return $listener;
        }

        foreach ($config['api-tools-mvc-auth']['authentication']['adapters'] as $type => $data) {
            if (! isset($data['adapter']) || ! is_string($data['adapter'])) {
                continue;
            }

            switch ($data['adapter']) {
                case 'Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter':
                    $adapter = AuthenticationHttpAdapterFactory::factory($type, $data, $services);
                    break;
                case 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter':
                    $adapter = AuthenticationOAuth2AdapterFactory::factory($type, $data, $services);
                    break;
                default:
                    $adapter = false;
                    break;
            }

            if ($adapter) {
                $listener->attach($adapter);
            }
        }

        return $listener;
    }
}
