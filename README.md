Laminas MVC Auth
===========

[![Build Status](https://travis-ci.org/laminas-api-tools/api-tools-mvc-auth.png)](https://travis-ci.org/laminas-api-tools/api-tools-mvc-auth)
[![Coverage Status](https://coveralls.io/repos/laminas-api-tools/api-tools-mvc-auth/badge.png?branch=master)](https://coveralls.io/r/laminas-api-tools/api-tools-mvc-auth)

Provide events for Authentication and Authorization in the Laminas MVC lifecycle.


Installation
------------

You can install using:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
```


Configuration
-------------

Services:
    ```authentication``` is provided and is an instance of Laminas\Auth\AuthenticationService
    with a NonPersistent storage adapter.
