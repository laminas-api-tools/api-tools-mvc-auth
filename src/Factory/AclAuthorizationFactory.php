<?php

declare(strict_types=1);

namespace Laminas\ApiTools\MvcAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization;
use Laminas\ApiTools\MvcAuth\Authorization\AclAuthorizationFactory as AclFactory;
use Laminas\Http\Request;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use function array_key_exists;
use function array_keys;
use function lcfirst;
use function sprintf;
use function strtr;

/**
 * Factory for creating an AclAuthorization instance from configuration
 */
class AclAuthorizationFactory implements FactoryInterface
{
    /** @var array */
    protected $httpMethods = [
        Request::METHOD_DELETE => true,
        Request::METHOD_GET    => true,
        Request::METHOD_PATCH  => true,
        Request::METHOD_POST   => true,
        Request::METHOD_PUT    => true,
    ];

    /**
     * Create and return an AclAuthorization instance.
     *
     * @param string $requestedName
     * @param null|array $options
     * @return AclAuthorization
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $this->getConfigFromContainer($container);
        return $this->createAclFromConfig($config);
    }

    /**
     * Create the AclAuthorization (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @return AclAuthorization
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, AclAuthorization::class);
    }

    /**
     * Generate the ACL instance based on the api-tools-mvc-auth "authorization" configuration
     *
     * Consumes the AclFactory in order to create the AclAuthorization instance.
     *
     * @param array $config
     * @return AclAuthorization
     */
    protected function createAclFromConfig(array $config)
    {
        $aclConfig     = [];
        $denyByDefault = false;

        if (array_key_exists('deny_by_default', $config)) {
            $denyByDefault = $aclConfig['deny_by_default'] = (bool) $config['deny_by_default'];
            unset($config['deny_by_default']);
        }

        foreach ($config as $controllerService => $privileges) {
            $this->createAclConfigFromPrivileges($controllerService, $privileges, $aclConfig, $denyByDefault);
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
     * @param bool $denyByDefault
     */
    protected function createAclConfigFromPrivileges(
        $controllerService,
        array $privileges,
        &$aclConfig,
        $denyByDefault
    ): void {
        // Normalize the controller service name.
        // laminas-mvc will always pass the name using namespace seprators, but
        // the admin may write the name using dash seprators.
        $controllerService = strtr($controllerService, '-', '\\');
        if (isset($privileges['actions'])) {
            foreach ($privileges['actions'] as $action => $methods) {
                $action      = lcfirst($action);
                $aclConfig[] = [
                    'resource'   => sprintf('%s::%s', $controllerService, $action),
                    'privileges' => $this->createPrivilegesFromMethods($methods, $denyByDefault),
                ];
            }
        }

        if (isset($privileges['collection'])) {
            $aclConfig[] = [
                'resource'   => sprintf('%s::collection', $controllerService),
                'privileges' => $this->createPrivilegesFromMethods($privileges['collection'], $denyByDefault),
            ];
        }

        if (isset($privileges['entity'])) {
            $aclConfig[] = [
                'resource'   => sprintf('%s::entity', $controllerService),
                'privileges' => $this->createPrivilegesFromMethods($privileges['entity'], $denyByDefault),
            ];
        }
    }

    /**
     * Create the list of HTTP methods defining privileges
     *
     * @param array $methods
     * @param bool $denyByDefault
     * @return array|null
     */
    protected function createPrivilegesFromMethods(array $methods, $denyByDefault)
    {
        $privileges = [];

        if (isset($methods['default']) && $methods['default']) {
            $privileges = $this->httpMethods;
            unset($methods['default']);
        }

        foreach ($methods as $method => $flag) {
            // If the flag evaluates true and we're denying by default, OR
            // if the flag evaluates false and we're allowing by default,
            // THEN no rule needs to be added
            if (
                ( $denyByDefault && $flag)
                || (! $denyByDefault && ! $flag)
            ) {
                if (isset($privileges[$method])) {
                    unset($privileges[$method]);
                }
                continue;
            }

            // Otherwise, we need to add a rule
            $privileges[$method] = true;
        }

        if (empty($privileges)) {
            return null;
        }

        return array_keys($privileges);
    }

    /**
     * Retrieve configuration from the container.
     *
     * Attempts to pull the 'config' service, and, further, the
     * api-tools-mvc-auth.authorization segment.
     *
     * @return array
     */
    private function getConfigFromContainer(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');

        if (! isset($config['api-tools-mvc-auth']['authorization'])) {
            return [];
        }

        return $config['api-tools-mvc-auth']['authorization'];
    }
}
