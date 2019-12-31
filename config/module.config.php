<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-mvc-auth for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-mvc-auth/blob/master/LICENSE.md New BSD License
 */

return array(
    'service_manager' => array(
        'aliases' => array(
            'authentication' => 'Laminas\ApiTools\MvcAuth\Authentication',
            'authorization' => 'Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface',
            'Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface' => 'Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization',
        ),
        'factories' => array(
            'Laminas\ApiTools\MvcAuth\Authentication' => 'Laminas\ApiTools\MvcAuth\Factory\AuthenticationServiceFactory',
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthenticationListenerFactory',
            'Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthHttpAdapterFactory',
            'Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization' => 'Laminas\ApiTools\MvcAuth\Factory\AclAuthorizationFactory',
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthorizationListenerFactory',
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultResourceResolverListenerFactory',
        ),
        'invokables' => array(
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener' => 'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener',
            'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener' => 'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener',
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Laminas\ApiTools\MvcAuth\Auth' => 'Laminas\ApiTools\MvcAuth\AuthController',
        ),
    ),
    'api-tools-mvc-auth' => array(
        'authentication' => array(
            /**
             *
            'http' => array(
                'accept_schemes' => array('basic', 'digest'),
                'realm' => 'My Web Site',
                'digest_domains' => '/',
                'nonce_timeout' => 3600,
                'htpasswd' => APPLICATION_PATH . '/data/htpasswd' // htpasswd tool generated
                'htdigest' => APPLICATION_PATH . '/data/htdigest' // @see http://www.askapache.com/online-tools/htpasswd-generator/
            ),
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
