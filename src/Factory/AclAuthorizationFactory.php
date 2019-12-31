<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\MvcAuth\Factory;

use Laminas\ApiTools\MvcAuth\Authorization\AclAuthorizationFactory as AclFactory;
use Laminas\Http\Request;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating an AclAuthorization instance from configuration
 */
class AclAuthorizationFactory implements FactoryInterface
{
    /**
     * @var array
     */
    protected $httpMethods = array(
        Request::METHOD_DELETE => true,
        Request::METHOD_GET    => true,
        Request::METHOD_PATCH  => true,
        Request::METHOD_POST   => true,
        Request::METHOD_PUT    => true,
    );

    /**
     * Create the DefaultAuthorizationListener
     *
     * @param ServiceLocatorInterface $services
     * @return \Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = array();
        if ($services->has('config')) {
            $config = $services->get('config');
        }

        return $this->createAclFromConfig($config);
    }

    /**
     * Generate the ACL instance based on the api-tools-mvc-auth "authorization" configuration
     *
     * Consumes the AclFactory in order to create the AclAuthorization instance.
     *
     * @param array $config
     * @return \Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization
     */
    protected function createAclFromConfig(array $config)
    {
        $aclConfig = array();

        if (isset($config['api-tools-mvc-auth'])
            && isset($config['api-tools-mvc-auth']['authorization'])
        ) {
            $config = $config['api-tools-mvc-auth']['authorization'];

            if (array_key_exists('deny_by_default', $config)) {
                $aclConfig['deny_by_default'] = (bool) $config['deny_by_default'];
                unset($config['deny_by_default']);
            }

            foreach ($config as $controllerService => $privileges) {
                $this->createAclConfigFromPrivileges($controllerService, $privileges, $aclConfig);
            }
        }

        return AclFactory::factory($aclConfig);
    }

    /**
     * Creates ACL configuration based on the privileges configured
     *
     * - Extracts a privilege per action
     * - Extracts privileges for each of "collection" and "entity" configured
     *
     * @param string $controllerService
     * @param array $privileges
     * @param array $aclConfig
     */
    protected function createAclConfigFromPrivileges($controllerService, array $privileges, &$aclConfig)
    {
        if (isset($privileges['actions'])) {
            foreach ($privileges['actions'] as $action => $methods) {
                $aclConfig[] = array(
                    'resource'   => sprintf('%s::%s', $controllerService, $action),
                    'privileges' => $this->createPrivilegesFromMethods($methods),
                );
            }
        }

        if (isset($privileges['collection'])) {
            $aclConfig[] = array(
                'resource'   => sprintf('%s::collection', $controllerService),
                'privileges' => $this->createPrivilegesFromMethods($privileges['collection']),
            );
        }

        if (isset($privileges['entity'])) {
            $aclConfig[] = array(
                'resource'   => sprintf('%s::entity', $controllerService),
                'privileges' => $this->createPrivilegesFromMethods($privileges['entity']),
            );
        }
    }

    /**
     * Create the list of HTTP methods defining privileges
     *
     * @param array $methods
     * @return array|null
     */
    protected function createPrivilegesFromMethods(array $methods)
    {
        $privileges = array();

        if (isset($methods['default']) && $methods['default']) {
            $privileges = $this->httpMethods;
            unset($methods['default']);
        }

        foreach ($methods as $method => $flag) {
            if (!$flag) {
                if (isset($privileges[$method])) {
                    unset($privileges[$method]);
                }
                continue;
            }
            $privileges[$method] = true;
        }

        if (empty($privileges)) {
            return null;
        }

        return array_keys($privileges);
    }
}
