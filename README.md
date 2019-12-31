Laminas MVC Auth
===========

[![Build Status](https://travis-ci.org/laminas-api-tools/api-tools-mvc-auth.svg?branch=master)](https://travis-ci.org/laminas-api-tools/api-tools-mvc-auth)
[![Coverage Status](https://coveralls.io/repos/github/laminas-api-tools/api-tools-mvc-auth/badge.svg?branch=master)](https://coveralls.io/github/laminas-api-tools/api-tools-mvc-auth?branch=master)

Introduction
------------

`api-tools-mvc-auth` is a Laminas module that adds services, events, and configuration that extends the base
Laminas MVC lifecycle to handle authentication and authorization.

For authentication, 3 primary methods are supported out of the box: HTTP Basic authentication,
HTTP Digest authentication, and OAuth2 (this requires Brent Shaffer's [OAuth2
Server](https://github.com/bshaffer/oauth2-server-php)).

For authorization, this particular module delivers a pre-dispatch time listener that will
identify if the given route match, along with the HTTP method, is authorized to be dispatched.

Requirements
------------
  
Please see the [composer.json](composer.json) file.

Installation
------------

Run the following `composer` command:

```console
$ composer require "laminas-api-tools/api-tools-mvc-auth"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "laminas-api-tools/api-tools-mvc-auth": "^1.4"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:


```php
return [
    /* ... */
    'modules' => [
        /* ... */
        'Laminas\ApiTools\MvcAuth',
    ],
    /* ... */
];
```

Configuration
-------------

### User Configuration

The top-level configuration key for user configuration of this module is `api-tools-mvc-auth`.  Under this
key, there are two sub-keys, one for `authentication` and the other for `authorization`.

#### Key: `authentication`

The `authentication` key is used for any configuration that is related to the process of
authentication, or the process of validating an identity.

##### Sub-key: `http`

The `http` sub-key is utilized for configuring an HTTP-based authentication scheme.  These schemes
utilize Laminas's `Laminas\Authentication\Adapter\Http` adapter, which implements both HTTP
Basic and HTTP Digest authentication.  To accomplish this, the HTTP adapter uses a file based
"resolver" in order to resolve the file containing credentials.  These implementation nuances can be
explored in the [Authentication portion of the Laminas manual](https://getlaminas.org/manual/2.0/en/modules/laminas.authentication.adapter.http.html).

The `http` sub-key has several fields:

- `accept_schemes`: *required*; an array of configured schemes; one or both of `basic` and `digest`.
- `realm`: *required*; this is typically a string that identifies the HTTP realm; e.g., "My Site".
- `digest_domains`: *required* for HTTP Digest; this is the relative URI for the protected area,
  typically `/`.
- `nonce_timeout`: *required* for HTTP Digest; the number of seconds in which to expire the digest
  nonce, typically `3600`.

Beyond those configuration options, one or both of the following resolver configurations is required:

- `htpasswd`: the path to a file created in the `htpasswd` file format
- `htdigest`: the path to a file created in the `htdigest` file format

An example might look like the following:

```php
'http' => [
    'accept_schemes' => ['basic', 'digest'],
    'realm' => 'My Web Site',
    'digest_domains' => '/',
    'nonce_timeout' => 3600,
    'htpasswd' => APPLICATION_PATH . '/data/htpasswd', // htpasswd tool generated
    'htdigest' => APPLICATION_PATH . '/data/htdigest', // @see http://www.askapache.com/online-tools/htpasswd-generator/
],
```

##### Sub-key: `map`

- Since 1.1.0.

The `map` subkey is used to map an API module (optionally, with a version
namespace) to a given authentication type (typically, one of `basic`, `digest`, or
`oauth2`). This can be used to enfore different authentication methods for
different APIs, or even versions of the same API.

```php
return [
    'api-tools-mvc-auth' => [
        'authentication' => [
            'map' => [
                'Status\V1' => 'basic',  // v1 only!
                'Status\V2' => 'oauth2', // v2 only!
                'Ping'      => 'digest', // all versions!
            ],
        ],
    ],
];
```

In the absence of a `map` subkey, if any authentication adapter configuration
is defined, that configuration will be used for any API.

**Note for users migrating from 1.0**: In the 1.0 series, authentication was
*per-application*, not per API. The migration to 1.1 should be seamless; if you
do not edit your authentication settings, or provide authentication information
to any APIs, your API will continue to act as it did. The first time you perform
one of these actions, the Admin API will create a map, mapping each version of
each service to the configured authentication scheme, and thus ensuring that
your API continues to work as previously configured, while giving you the
flexibility to define authentication per-API and per-version in the future.

##### Sub-key: `types`

- Since 1.1.0.

Starting in 1.1.0, the concept of authentication adapters was provided. Adapters
"provide" one or more authentication types; these are then used internally to
determine which adapter to use, as well as by the Admin API to allow mapping
APIs to specific authentication types.

In some instances you may be using listeners or other facilities for
authenticating an API. In order to allow mapping these (which is primarily a
documentation feature in such instances), the `types` subkey exists. This key is
an array of string authentication types:

```php
return [
    'api-tools-mvc-auth' => [
        'authentication' => [
            'types' => [
                'token',
                'key',
            ],
        ],
    ],
];
```

This key and its contents **must** be created manually.

##### Sub-key: `adapters`

- Since 1.1.0.

Starting in 1.1.0, with the introduction of adapters, you can also configure
named HTTP and OAuth2 adapters. The name provided will be used as the
authentication type for purposes of mapping APIs to an authentication adapter.

The format for the `adapters` key is a key/value pair, with the key acting as
the type, and the value as configuration for providing a
`Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter` or
`Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter` instance, as follows:

```php
return [
    'api-tools-mvc-auth' => [
        'authentication' => [
            'adapters' => [
                'api' => [
                    // This defines an HTTP adapter that can satisfy both
                    // basic and digest.
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter',
                    'options' => [
                        'accept_schemes' => ['basic', 'digest'],
                        'realm' => 'api',
                        'digest_domains' => 'https://example.com',
                        'nonce_timeout' => 3600,
                        'htpasswd' => 'data/htpasswd',
                        'htdigest' => 'data/htdigest',
                    ],
                ],
                'user' => [
                    // This defines an OAuth2 adapter backed by PDO.
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => [
                        'adapter' => 'pdo',
                        'dsn' => 'mysql:host=localhost;dbname=oauth2',
                        'username' => 'username',
                        'password' => 'password',
                        'options' => [
                            1002 => 'SET NAMES utf8', // PDO::MYSQL_ATTR_INIT_COMMAND
                        ],
                    ],
                ],
                'client' => [
                    // This defines an OAuth2 adapter backed by Mongo.
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => [
                        'adapter' => 'mongo',
                        'locator_name' => 'SomeServiceName', // If provided, pulls the given service
                        'dsn' => 'mongodb://localhost',
                        'database' => 'oauth2',
                        'options' => [
                            'username' => 'username',
                            'password' => 'password',
                            'connectTimeoutMS' => 500,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

#### Key: `authorization`

#### Sub-Key: `deny_by_default`

`deny_by_default` toggles the default behavior for the `Laminas\Permissions\Acl` implementation.  The
default value is `false`, which means that if no authenticated user is present, and no permissions
rule applies for the current resource, then access is allowed. Change this setting to `true` to
require authenticated identities by default.

Example:

```php
'deny_by_default' => false,
```

> ##### deny_by_default with api-tools-oauth2
>
> When using `deny_by_default => true` with > [api-tools-oauth2](https://github.com/laminas-api-tools/api-tools-oauth2),
> you will need to explicitly allow POST on the OAuth2 controller in order for Authentication
> requests to be made.
> 
> As an example:
>
> ```php
> 'authorization' => [
>     'deny_by_default' => true,
>     'Laminas\\ApiTools\\OAuth2\\Controller\\Auth' => [
>         'actions' => [
>             'token' => [
>                 'GET'    => false,
>                 'POST'   => true,   // <-----
>                 'PATCH'  => false,
>                 'PUT'    => false,
>                 'DELETE' => false,
>             ],
>         ],
>     ],
> ],
> ```

#### Sub-Key: Controller Service Name

Under the `authorization` key is an array of _controller service name_ keyed authorization
configuration settings.  The structure of these arrays depends on the type of the controller
service that you're attempting to grant or restrict access to.

For the typical Laminas based action controller, this array is keyed with `actions`.  Under this
key, each action name for the given controller service is associated with a *permission array*.

For [api-tools-rest](https://github.com/laminas-api-tools/api-tools-rest)-based controllers, a top level key of either
`collection` or `entity` is used.  Under each of these keys will be an associated *permission
array*.

A **permission array** consists of a keyed array of either `default` or an HTTP method.  The
values for each of these will be a boolean value where `true` means _an authenticated user
is required_ and where `false` means _an authenticated user is *not* required_.  If an action
or HTTP method is not idendified, the `default` value will be assumed.  If there is no default,
the behavior of the `deny_by_default` key (discussed above) will be assumed.

Below is an example:

```php
'authorization' => [
    'Controller\Service\Name' => [
        'actions' => [
            'action' => [
                'default' => boolean,
                'GET' => boolean,
                'POST' => boolean,
                // etc.
            ],
        ],
        'collection' => [
            'default' => boolean,
            'GET' => boolean,
            'POST' => boolean,
            // etc.
        ],
        'entity' => [
            'default' => boolean,
            'GET' => boolean,
            'POST' => boolean,
            // etc.
        ],
    ],
],
```

### System Configuration

The following configuration is provided in `config/module.config.php` to enable the module to
function:

```php
'service_manager' => [
    'aliases' => [
        'authentication' => 'Laminas\ApiTools\MvcAuth\Authentication',
        'authorization' => 'Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface',
        'Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface' => 'Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization',
    ],
    'factories' => [
        'Laminas\ApiTools\MvcAuth\Authentication' => 'Laminas\ApiTools\MvcAuth\Factory\AuthenticationServiceFactory',
        'Laminas\ApiTools\MvcAuth\ApacheResolver' => 'Laminas\ApiTools\MvcAuth\Factory\ApacheResolverFactory',
        'Laminas\ApiTools\MvcAuth\FileResolver' => 'Laminas\ApiTools\MvcAuth\Factory\FileResolverFactory',
        'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthenticationListenerFactory',
        'Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthHttpAdapterFactory',
        'Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization' => 'Laminas\ApiTools\MvcAuth\Factory\AclAuthorizationFactory',
        'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultAuthorizationListenerFactory',
        'Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener' => 'Laminas\ApiTools\MvcAuth\Factory\DefaultResourceResolverListenerFactory',
    ],
    'invokables' => [
        'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener' => 'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener',
        'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener' => 'Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener',
    ],
],
```

These services will be described in the events and services section.

Laminas Events
----------

### Events

#### Laminas\ApiTools\MvcAuth\MvcAuthEvent::EVENT_AUTHENTICATION (a.k.a "authentication")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `500` priority.  It is registered
via the `Laminas\ApiTools\MvcAuth\MvcRouteListener` event listener aggregate.

#### Laminas\ApiTools\MvcAuth\MvcAuthEvent::EVENT_AUTHENTICATION_POST (a.k.a "authentication.post")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `499` priority.  It is
registered via the `Laminas\ApiTools\MvcAuth\MvcRouteListener` event listener aggregate.

#### Laminas\ApiTools\MvcAuth\MvcAuthEvent::EVENT_AUTHORIZATION (a.k.a "authorization")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `-600` priority.  It is
registered via the `Laminas\ApiTools\MvcAuth\MvcRouteListener` event listener aggregate.

#### Laminas\ApiTools\MvcAuth\MvcAuthEvent::EVENT_AUTHORIZATION_POST (a.k.a "authorization.post")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `-601` priority.  It is
registered via the `Laminas\ApiTools\MvcAuth\MvcRouteListener` event listener aggregate.

#### Laminas\ApiTools\MvcAuth\MvcAuthEvent object

The `MvcAuthEvent` object provides contextual information when any authentication
or authorization event is triggered.  It persists the following:

- identity: `setIdentity()` and `getIdentity()`
- authentication service: `setAuthentication()` and `getAuthentication()`
- authorization service: `setAuthorization()` and `getAuthorization()`
- authorization result: `setIsAuthorized` and `isAuthorized()`
- original MVC event: `getMvcEvent()`

### Listeners

#### Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION` event.  It is primarily
responsible for preforming any authentication and ensuring that an authenticated
identity is persisted in both the `MvcAuthEvent` and `MvcEvent` objects (the latter under the event
parameter `Laminas\ApiTools\MvcAuth\Identity`).

#### Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION_POST` event.  It is primarily
responsible for determining if an unsuccessful authentication was preformed, and in that case
it will attempt to set a `401 Unauthorized` status on the `MvcEvent`'s response object.

#### Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener

This listener is attached to the `MvcAuth::EVENT_AUTHORIZATION` event.  It is primarily
responsible for executing the `isAuthorized()` method on the configured authorization service.

#### Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener

This listener is attached to the `MvcAuth::EVENT_AUTHORIZATION_POST` event.  It is primarily
responsible for determining if the current request is authorized.   In the case where the current
request is not authorized, it will attempt to set a `403 Forbidden` status on the `MvcEvent`'s
response object.

#### Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION_POST` with a priority of `-1`.
It is primarily responsible for creating and persisting a special name in the current event
for api-tools-rest-based controllers when used in conjunction with `api-tools-rest` module.

Laminas Services
------------

#### Controller Plugins

This module exposes the controller plugin `getIdentity()`, mapping to
`Laminas\ApiTools\MvcAuth\Identity\IdentityPlugin`. This plugin will return the identity discovered during
authentication as injected into the `Laminas\Mvc\MvcEvent`'s `Laminas\ApiTools\MvcAuth\Identity` parameter. If no
identity is present in the `MvcEvent`, or the identity present is not an instance of
`Laminas\ApiTools\MvcAuth\Identity\IdentityInterface`, an instance of `Laminas\ApiTools\MvcAuth\Identity\GuestIdentity` will be
returned.

#### Event Listener Services

The following services are provided and serve as event listeners:

- `Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener`
- `Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener`
- `Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener`
- `Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener`
- `Laminas\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener`

#### Laminas\ApiTools\MvcAuth\Authentication (a.k.a "authentication")

This is an instance of `Laminas\Authentication\AuthenticationService`.

#### Laminas\ApiTools\MvcAuth\Authentication\AuthHttpAdapter

This is an instance of `Laminas\Authentication\Adapter\Http`.

#### Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization (a.k.a "authorization", "Laminas\ApiTools\MvcAuth\Authorization\AuthorizationInterface")

This is an instance of `Laminas\ApiTools\MvcAuth\Authorization\AclAuthorization`, which in turn is an extension
of `Laminas\Permissions\Acl\Acl`.


#### Laminas\ApiTools\MvcAuth\ApacheResolver

This is an instance of `Laminas\Authentication\Adapter\Http\ApacheResolver`. 
You can override the ApacheResolver with your own resolver by providing a custom factory. 

#### Laminas\ApiTools\MvcAuth\FileResolver

This is an instance of `Laminas\Authentication\Adapter\Http\FileResolver`.
You can override the FileResolver with your own resolver by providing a custom factory.

### Authentication Adapters

- Since 1.1.0

Authentication adapters provide the most direct means for adding custom
authentication facilities to your APIs. Adapters implement
`Laminas\ApiTools\MvcAuth\Authentication\AdapterInterface`:

```php
namespace Laminas\ApiTools\MvcAuth\Authentication;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;

interface AdapterInterface
{
    /**
     * @return array Array of types this adapter can handle.
     */
    public function provides();

    /**
     * Attempt to match a requested authentication type
     * against what the adapter provides.
     *
     * @param string $type
     * @return bool
     */
    public function matches($type);

    /**
     * Attempt to retrieve the authentication type based on the request.
     *
     * Allows an adapter to have custom logic for detecting if a request
     * might be providing credentials it's interested in.
     *
     * @param Request $request
     * @return false|string
     */
    public function getTypeFromRequest(Request $request);

    /**
     * Perform pre-flight authentication operations.
     *
     * Use case would be for providing authentication challenge headers.
     *
     * @param Request $request
     * @param Response $response
     * @return void|Response
     */
    public function preAuth(Request $request, Response $response);

    /**
     * Attempt to authenticate the current request.
     *
     * @param Request $request
     * @param Response $response
     * @param MvcAuthEvent $mvcAuthEvent
     * @return false|IdentityInterface False on failure, IdentityInterface
     *     otherwise
     */
    public function authenticate(Request $request, Response $response, MvcAuthEvent $mvcAuthEvent);
}
```

The `provides()` method should return an array of strings, each an
authentication "type" that this adapter provides; as an example, the provided
`Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter` can provide `basic` and/or `digest`.

The `matches($type)` should test the given `$type` against what the adapter
provides to determine if it can handle an authentication request. Typically,
this can be done with `return in_array($type, $this->provides(), true);`

The `getTypeFromRequest()` method can be used to match an incoming request to
the authentication type it resolves, if any. Examples might be deconstructing
the `Authorization` header, or a custom header such as `X-Api-Token`.

The `preAuth()` method can be used to provide client challenges; typically,
this will only ever be used by the included `HttpAdapter`.

Finally, the `authenticate()` method is used to attempt to authenticate an
incoming request. I should return either a boolean `false`, indicating
authentictaion failed, or an instance of
`Laminas\ApiTools\MvcAuth\Identity\IdentityInterface`; if the latter is returned, that
identity will be used for the duration of the request.

Adapters are attached to the `DefaultAuthenticationListener`. To attach your
custom adapter, you will need to do one of the following:

- Define named HTTP and/or OAuth2 adapters via configuration.
- During an event listener, pull your adapter and the
  `DefaultAuthenticationListener` services, and attach your adapter to the
  latter.
- Create a `DelegatorFactory` for the `DefaultAuthenticationListener` that
  attaches your custom adapter before returning the listener.

#### Defining named HTTP and/or OAuth2 adapters

Since HTTP and OAuth2 support is built-in, `api-tools-mvc-auth` provides a
configuration-driven approach for creating named adapters of these types. Each
requires a unique key under the `api-tools-mvc-auth.authentication.adapters`
configuration, and each type has its own format.

```php
return [
    /* ... */
    'api-tools-mvc-auth' => [
        'authentication' => [
            'adapters' => [
                'api' => [
                    // This defines an HTTP adapter that can satisfy both
                    // basic and digest.
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\HttpAdapter',
                    'options' => [
                        'accept_schemes' => ['basic', 'digest'],
                        'realm' => 'api',
                        'digest_domains' => 'https://example.com',
                        'nonce_timeout' => 3600,
                        'htpasswd' => 'data/htpasswd',
                        'htdigest' => 'data/htdigest',
                    ],
                ],
                'user' => [
                    // This defines an OAuth2 adapter backed by PDO.
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => [
                        'adapter' => 'pdo',
                        'dsn' => 'mysql:host=localhost;dbname=oauth2',
                        'username' => 'username',
                        'password' => 'password',
                        'options' => [
                            1002 => 'SET NAMES utf8', // PDO::MYSQL_ATTR_INIT_COMMAND
                        ],
                    ],
                ],
                'client' => [
                    // This defines an OAuth2 adapter backed by Mongo.
                    'adapter' => 'Laminas\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => [
                        'adapter' => 'mongo',
                        'locator_name' => 'SomeServiceName', // If provided, pulls the given service
                        'dsn' => 'mongodb://localhost',
                        'database' => 'oauth2',
                        'options' => [
                            'username' => 'username',
                            'password' => 'password',
                            'connectTimeoutMS' => 500,
                        ],
                    ],
                ],
            ],
            /* ... */
        ],
        /* ... */
    ],
    /* ... */
];
```

The above configuration would provide the authentication types
`['api-basic', 'api-digest', 'user', 'client']` to your application, which
can each them be associated in the authentication type map.

If you use `api-tools-admin`'s Admin API and/or the Laminas API Tools UI to
configure authentication adapters, the above configuration will be created for
you.

#### Attaching an adapter during an event listener

The best event to attach to in this circumstances is the "authentication" event.
When doing so, you'll want to attach at a priority > 1 to ensure it executes
before the `DefaultAuthenticationListener`.

In the following example, we'll assume you've defined a service named
`MyCustomAuthenticationAdapter` that returns an `AdapterInterface`
implementation, and that the class is the `Module` class of your API or a module
in your application.

```php
class Module
{
    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $events   = $app->getEventManager();
        $services = $app->getServiceManager();

        $events->attach(
            'authentication',
            function ($e) use ($services) {
                $listener = $services->get('Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener')
                $adapter = $services->get('MyCustomAuthenticationAdapter');
                $listener->attach($adapter);
            },
            1000
        );
    }
}
```

By returning nothing, the `DefaultAuthenticationListener` will continue to
execute, but will now also have the new adapter attached.

#### Using a delegator factory

Delegator Factories are a way to "decorate" an instance returned by the Laminas
Framework `ServiceManager` in order to provide pre-conditions or alter the
instance normally returned. In our case, we want to attach an adapter after the
instance is created, but before it's returned.

In the following example, we'll assume you've defined a service named
`MyCustomAuthenticationAdapter` that returns an `AdapterInterface`
implementation. The following is a delegator factory for the `DefaultAuthenticationListener` that will inject our adapter.

```php
use Laminas\ServiceManager\DelegatorFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class CustomAuthenticationDelegatorFactory implements DelegatorFactoryInterface
{
    public function createDelegatorWithName(
        ServiceLocatorInterface $services,
        $name,
        $requestedName,
        $callback
    ) {
        $listener  = $callback();
        $listener->attach($services->get('MyCustomAuthenticationAdapter');
        return $listener;
    }
}
```

We then need to tell the `ServiceManager` about the delegator factory; we do this in our module's `config/module.config.php`, or one of the `config/autoload/` configuration files:

```php
return [
    /* ... */
    'service_manager' => [
        /* ... */
        'delegators' => [
            'Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener' => [
                'CustomAuthenticationDelegatorFactory',
            ],
        ],
    ],
    /* ... */
];
```

Once configured, our adapter will be attached to every instance of the `DefaultAuthenticationListener` that is retrieved.
