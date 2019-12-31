<?php // @codingStandardsIgnoreFile
/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

return array(
    'controller_plugins' => array(
        'invokables' => array(
            'getidentity' => 'Laminas\ApiTools\MvcAuth\Identity\IdentityPlugin',
        ),
    ),
    'service_manager' => array(
        'aliases' => array(
            'authentication' => 'Laminas\ApiTools\MvcAuth\Authentication',
            'authorization' => 'Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface',
            'Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface' => 'Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization',
        ),
        'delegators' => array(
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener' => array(
                'Laminas\ApiTools\MvcAuth\Factory\AuthenticationAdapterDelegatorFactory',
            ),
        ),
        'factories' => array(
            'Laminas\ApiTools\MvcAuth\Authentication' => 'Laminas\ApiTools\MvcAuth\Factory\AuthenticationServiceFactory',
            'Laminas\ApiTools\MvcAuth\ApacheResolver' => 'Laminas\ApiTools\MvcAuth\Factory\ApacheResolverFactory',
            'Laminas\ApiTools\MvcAuth\FileResolver' => 'Laminas\ApiTools\MvcAuth\Factory\FileResolverFactory',
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthenticationListenerFactory',
            'Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthHttpAdapterFactory',
            'Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization' => 'Laminas\ApiTools\MvcAuth\Factory\AclAuthorizationFactory',
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthorizationListenerFactory',
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultResourceResolverListenerFactory',
            'Laminas\ApiTools\OAuth2\Service\OAuth2Server' => 'Laminas\ApiTools\MvcAuth\Factory\NamedOAuth2ServerFactory',
        ),
        'invokables' => array(
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener' => 'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener',
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener' => 'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener',
        ),
    ),
    'api-tools-mvc-auth' => array(
        'authentication' => array(
            /* First, we define authentication configuration types. These have
             * the keys:
             * - http
             * - oauth2
             *
             * Note: as of 1.1, these are deprecated.
             *
            'http' => array(
                'accept_schemes' => array('basic', 'digest'),
                'realm' => 'My Web Site',
                'digest_domains' => '/',
                'nonce_timeout' => 3600,
                'htpasswd' => APPLICATION_PATH . '/data/htpasswd' // htpasswd tool generated
                'htdigest' => APPLICATION_PATH . '/data/htdigest' // @see http://www.askapache.com/online-tools/htpasswd-generator/
            ),
             *
             * Starting in 1.1, we have an "adapters" key, which is a key/value
             * pair of adapter name -> adapter configuration information. This
             * looks like the following for the HTTP basic/digest and OAuth2
             * adapters:
            'adapters' => array
                'api' => array(
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter',
                    'options' => array(
                        'accept_schemes' => array('basic', 'digest'),
                        'realm' => 'api',
                        'digest_domains' => 'https://example.com',
                        'nonce_timeout' => 3600,
                        'htpasswd' => 'data/htpasswd',
                        'htdigest' => 'data/htdigest',
                    ),
                ),
                'user' => array(
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'pdo',
                        'dsn' => 'mysql:host=localhost;dbname=oauth2',
                        'username' => 'username',
                        'password' => 'password',
                        'options' => aray(
                            1002 => 'SET NAMES utf8', // PDO::MYSQL_ATTR_INIT_COMMAND
                        ),
                    ),
                ),
                'client' => array(
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'mongo',
                        'locator_name' => 'SomeServiceName', // If provided, pulls the given service
                        'dsn' => 'mongodb://localhost',
                        'database' => 'oauth2',
                        'options' => array(
                            'username' => 'username',
                            'password' => 'password',
                            'connectTimeoutMS' => 500,
                        ),
                    ),
                ),
            ),
             *
             * Next, we also have a "map", which maps an API module (with
             * optional version) to a given authentication type (one of basic,
             * digest, or oauth2):
            'map' => array(
                'ApiModuleName' => 'oauth2',
                'OtherApi\V2' => 'basic',
                'AnotherApi\V1' => 'digest',
            ),
             *
             * We also allow you to specify custom authentication types that you
             * support via listeners; by adding them to the configuration, you
             * ensure that they will be available for mapping modules to
             * authentication types in the Admin.
            'types' => array(
                'token',
                'key',
                'etc',
            )
             */
        ),
        'authorization' => array(
            // Toggle the following to true to change the ACL creation to
            // require an authenticated user by default, and thus selectively
            // allow unauthenticated users based on the rules.
            'deny_by_default' => false,

            /*
             * Rules indicating what controllers are behind authentication.
             *
             * Keys are controller service names.
             *
             * Values are arrays with either the key "actions" and/or one or
             * more of the keys "collection" and "entity".
             *
             * The "actions" key will be a set of action name/method pairs.
             * The "collection" and "entity" keys will have method values.
             *
             * Method values are arrays of HTTP method/boolean pairs. By
             * default, if an HTTP method is not present in the list, it is
             * assumed to be open (i.e., not require authentication). The
             * special key "default" can be used to set the default flag for
             * all HTTP methods.
             *
            'Controller\Service\Name' => array(
                'actions' => array(
                    'action' => array(
                        'default' => boolean,
                        'GET' => boolean,
                        'POST' => boolean,
                        // etc.
                    ),
                ),
                'collection' => array(
                    'default' => boolean,
                    'GET' => boolean,
                    'POST' => boolean,
                    // etc.
                ),
                'entity' => array(
                    'default' => boolean,
                    'GET' => boolean,
                    'POST' => boolean,
                    // etc.
                ),
            ),
             */
        ),
    ),
);
