Dependency Injection Container - Koded
--------------------------------------

[![Latest Stable Version](https://img.shields.io/packagist/v/koded/container.svg)](https://packagist.org/packages/koded/container)
[![Build Status](https://travis-ci.com/kodedphp/container.svg?branch=master)](https://travis-ci.com/kodedphp/container)
[![Code Coverage](https://scrutinizer-ci.com/g/kodedphp/container/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kodedphp/container/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kodedphp/container/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kodedphp/container/?branch=master)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/kodedphp/container/master)](https://github.com/kodedphp/container)
[![Minimum PHP Version: 7.2](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg)](https://php.net/)


`koded/container` is an OOP application bootstrapping and wiring library.
In other words, `Koded\DIContainer` implements a **design pattern** called **Dependency Injection**.
The main principle of DIP is to separate the behavior from dependency resolution.

```bash
composer require koded/container
```

## Example

Let's look at a blog application that has
- interfaces for the database repositories and corresponding implementations
- a shared PDO instance
- a service class for the blog content fetching
- a handler class that maps the request method


```php
use PDO;

interface PostRepository {
    public function findBySlug(string $slug);
}

interface UserRepository {
    public function findById(int $id);
}

class DatabasePostRepository implements PostRepository {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    public function findBySlug(string $slug) {
        // $this->pdo ...
    }
}

class DatabaseUserRepository implements UserRepository {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    public function findById(int $id) {
        // $this->pdo ...
    }
}
```

Somewhere we may have a service class that uses the dependent repositories:
```php
class PostService {
    private $post, $user;
    public function __construct(PostRepository $post, UserRepository $user) {
        $this->post = $post;
        $this->user = $user;
    }

    // a service method that uses the post and user repository instances
    public function findBlogPostBySlug(string $slug) {
        $post = $this->post->findBySlug($slug);
        $user = $this->user->findById($post->userId());
        // ... do something with the results, create a result data structure...
    }
}
```

Then somewhere we might have a handler/controller that asks for it's own dependencies:
```php
class HttpPostHandler {
    public function get(ServerRequestInterface $request, PostService $service): ResponseInterface {
        $slug = slugify($request->getUri()->getPath());
        $post = $service->findBlogPostBySlug($slug);

        // some PSR-7 compatible response object
        return new ServerResponse($post);
    }
}
```

This is the bootstrapping / wiring application module
```php
class BlogModule implements DIModule {
    public function configure(DIContainer $container): void {
        // bind interfaces to concrete class implementations
        $container->bind(PostRepository::class, DatabasePostRepository::class);
        $container->bind(UserRepository::class, DatabaseUserRepository::class);
        $container->bind(ServerRequestInterface::class, /*some PSR-7 server request class name*/);
        
        // share one PDO instance
        $container->singleton(PDO::class, ['sqlite:database.db']);
    }
}
```

And finally in the dispatcher file, we execute the request
```php
// index.php

// (resolved through an HTTP router or other means)
$resolvedDispatcher = HttpPostHandler::class;
$resolvedMethod = 'get';

// by invoking the container
$response = (new DIContainer(new BlogModule))([$resolvedDispatcher, $resolvedMethod]);

// we have a `$response` object to output the blog content
// ex. `echo $response->getBody()->getContents();`
```

The container implements the [__invoke()][invoke] method, so the instance can be used as a function:
```php
$container('method', 'arguments');
```

> To be continued...


Code quality
------------

```shell script
vendor/bin/infection --threads=4
vendor/bin/phpbench run --report=default
vendor/bin/phpunit
```


License
-------
[![Software license](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](LICENSE)

The code is distributed under the terms of [The 3-Clause BSD license](LICENSE).


[invoke]: https://php.net/manual/en/language.oop5.magic.php#object.invoke