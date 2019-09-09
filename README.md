Koded Dependency Injection Container
====================================

[![Latest Stable Version](https://img.shields.io/packagist/v/koded/di.svg)](https://packagist.org/packages/koded/di)
[![Build Status](https://travis-ci.org/kodedphp/di.svg?branch=master)](https://travis-ci.org/kodedphp/container)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/kodedphp/di/master)](https://github.com/kodedphp/container)
[![Minimum PHP Version: 7.2](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![Software license](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](LICENSE)

koded/di - Dependency Injection Container
-----------------------------------------

`koded/di` is a SOLID OOP application bootstrapping and wiring library.
In other words, `Koded\DIContainer` implements a **design pattern** called **Dependency Injection**.
The main principle of DIP is to separate the behavior from dependency resolution.

## Example

Lets look at a simple blog application that
- has interfaces for the database repositories and corresponding implementations
- uses a shared PDO instance
- a service class for the blog content fetching
- a dispatcher class that executes the request


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

Then somewhere we might have a dispatcher/controller that asks for it's own dependencies:
```php
class PostCommandDispatcher {
    public function get(ServerRequestInterface $request, PostService $service): ResponseInterface {
        $slug = slugify($request->getUri()->getPath());
        $post = $service->findBlogPostBySlug($slug);

        // some PSR-7 compatible response object
        return new ServerResponse($post);
    }
}
```

And finally in the dispatcher file we execute the request
```php
// index.php

// resolved through an HTTP router or other means
$resolvedDispatcher = PostCommandDispatcher::class;
$resolvedMethod = 'get';

$response = (new DIContainer(new BlogModule))
    ->call([$resolvedDispatcher, $resolvedMethod]);

// use the `$response` object to output the blog content
// `echo $response->getBody()->getContents();`
```

> To be continued...