<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\Authentication\Adapter\Http as HttpAuth;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating the DefaultAuthHttpAdapterFactory from configuration
 */
class DefaultAuthHttpAdapterFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $services
     * @throws ServiceNotCreatedException
     * @return false|HttpAuth
     */
    public function createService(ServiceLocatorInterface $services)
    {
        // If no configuration present, nothing to create
        if (!$services->has('config')) {
            return false;
        }

        $config = $services->get('config');

        // If no HTTP adapter configuration present, nothing to create
        if (!isset($config['api-tools-mvc-auth']['authentication']['http'])) {
            return false;
        }

        $httpConfig = $config['api-tools-mvc-auth']['authentication']['http'];

        if (!isset($httpConfig['accept_schemes']) || !is_array($httpConfig['accept_schemes'])) {
            throw new ServiceNotCreatedException(
                '"accept_schemes" is required when configuring an HTTP authentication adapter'
            );
        }

        if (!isset($httpConfig['realm'])) {
            throw new ServiceNotCreatedException('"realm" is required when configuring an HTTP authentication adapter');
        }

        if (in_array('digest', $httpConfig['accept_schemes'])) {
            if (!isset($httpConfig['digest_domains'])
                || !isset($httpConfig['nonce_timeout'])
            ) {
                throw new ServiceNotCreatedException(
                    'Both "digest_domains" and "nonce_timeout" are required '
                    . 'when configuring an HTTP digest authentication adapter'
                );
            }
        }

        $httpAdapter = new HttpAuth(array_merge(
            $httpConfig,
            array(
                'accept_schemes' => implode(' ', $httpConfig['accept_schemes'])
            )
        ));

        if (in_array('basic', $httpConfig['accept_schemes'])
            && $services->has('Laminas\ApiTools\MvcAuth\ApacheResolver')
        ) {
            $resolver = $services->get('Laminas\ApiTools\MvcAuth\ApacheResolver');
            if ($resolver !== false) {
                $httpAdapter->setBasicResolver($resolver);
            }
        }

        if (in_array('digest', $httpConfig['accept_schemes'])
            && $services->has('Laminas\ApiTools\MvcAuth\FileResolver')
        ) {
            $resolver = $services->get('Laminas\ApiTools\MvcAuth\FileResolver');
            if ($resolver !== false) {
                $httpAdapter->setDigestResolver($resolver);
            }
        }

        return $httpAdapter;
    }
}
