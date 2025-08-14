<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    // PDO Connection
    PDO::class => function (): PDO {
        return \App\Infrastructure\Drivers\PDOConnection::getConnection();
    },

    // Redis Connection
    \Predis\Client::class => function (): Predis\Client {
        return \App\Infrastructure\Drivers\RedisConnection::getConnection();
    },

    // Repository implementations
    \App\Application\Ports\ClientRepository::class => \DI\autowire(\App\Infrastructure\Repository\ClientRepositoryImpl::class),
    \App\Application\Ports\SessionRepository::class => \DI\autowire(\App\Infrastructure\Repository\SessionRepositoryImpl::class),
    \App\Application\Ports\ProductRepository::class => \DI\autowire(\App\Infrastructure\Repository\ProductRepositoryImpl::class),
    \App\Application\Ports\CacheRepository::class => \DI\autowire(\App\Infrastructure\Cache\RedisRepositoryImpl::class),
    
    // FavoriteProductRepository with transparent caching
    \App\Application\Ports\FavoriteProductRepository::class => function ($container) {
        $baseRepository = new \App\Infrastructure\Repository\FavoriteProductRepositoryImpl(
            $container->get(PDO::class)
        );
        
        $cacheRepository = $container->get(\App\Application\Ports\CacheRepository::class);
        
        return new \App\Infrastructure\Repository\CachedFavoriteProductRepository(
            $baseRepository,
            $cacheRepository
        );
    },

    // Application services
    \App\Application\Services\AuthService::class => \DI\autowire(\App\Application\Services\AuthService::class),
    \App\Application\Services\ClientService::class => \DI\autowire(\App\Application\Services\ClientService::class),
    \App\Application\Services\FavoriteService::class => \DI\autowire(\App\Application\Services\FavoriteService::class),
    \App\Application\Services\ProductService::class => \DI\autowire(\App\Application\Services\ProductService::class),

    // Controllers
    \App\Infrastructure\Http\Controllers\AuthController::class => \DI\autowire(\App\Infrastructure\Http\Controllers\AuthController::class),
    \App\Infrastructure\Http\Controllers\ClientController::class => \DI\autowire(\App\Infrastructure\Http\Controllers\ClientController::class),
    \App\Infrastructure\Http\Controllers\FavoriteController::class => \DI\autowire(\App\Infrastructure\Http\Controllers\FavoriteController::class),
    \App\Infrastructure\Http\Controllers\ProductController::class => \DI\autowire(\App\Infrastructure\Http\Controllers\ProductController::class),
    \App\Infrastructure\Http\Controllers\DocumentationController::class => \DI\autowire(\App\Infrastructure\Http\Controllers\DocumentationController::class),
]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Authentication routes
$app->group('/api/v1/auth', function ($group) use ($container) {
    $group->post('/login', [\App\Infrastructure\Http\Controllers\AuthController::class, 'login']);

    $group->post('/refresh', [\App\Infrastructure\Http\Controllers\AuthController::class, 'refresh'])
        ->add(new \App\Infrastructure\Http\Middleware\AuthMiddleware($container->get(\App\Application\Services\AuthService::class)));

    $group->post('/logout', [\App\Infrastructure\Http\Controllers\AuthController::class, 'logout'])
        ->add(new \App\Infrastructure\Http\Middleware\AuthMiddleware($container->get(\App\Application\Services\AuthService::class)));

    $group->get('/verify', [\App\Infrastructure\Http\Controllers\AuthController::class, 'verify'])
        ->add(new \App\Infrastructure\Http\Middleware\AuthMiddleware($container->get(\App\Application\Services\AuthService::class)));
});

// Unprotected routes
$app->group('/api/v1', function ($group)  {
    $group->get('/clients', [\App\Infrastructure\Http\Controllers\ClientController::class, 'index']);
    $group->get('/clients/{id:[0-9]+}', [\App\Infrastructure\Http\Controllers\ClientController::class, 'show']);
    $group->post('/clients', [\App\Infrastructure\Http\Controllers\ClientController::class, 'store']);
    
    // Documentation routes
    $group->get('/docs', [\App\Infrastructure\Http\Controllers\DocumentationController::class, 'getSwaggerUIHtmlPage']);
    $group->get('/docs/openapi', [\App\Infrastructure\Http\Controllers\DocumentationController::class, 'getOpenApiSpecJson']);
});

// Protected routes
$app->group('/api/v1', function ($group) {
    $group->put('/clients/{id:[0-9]+}', [\App\Infrastructure\Http\Controllers\ClientController::class, 'update']);
    $group->delete('/clients/{id:[0-9]+}', [\App\Infrastructure\Http\Controllers\ClientController::class, 'destroy']);

    $group->get('/products', [\App\Infrastructure\Http\Controllers\ProductController::class, 'index']);
    $group->get('/products/{id:[0-9]+}', [\App\Infrastructure\Http\Controllers\ProductController::class, 'show']);

    $group->get('/clients/{clientId:[0-9]+}/favorites', [\App\Infrastructure\Http\Controllers\FavoriteController::class, 'index']);
    $group->post('/clients/{clientId:[0-9]+}/favorites', [\App\Infrastructure\Http\Controllers\FavoriteController::class, 'store']);
    $group->delete('/clients/{clientId:[0-9]+}/favorites/{productId:[0-9]+}', [\App\Infrastructure\Http\Controllers\FavoriteController::class, 'destroy']);
})->add(new \App\Infrastructure\Http\Middleware\AuthMiddleware($container->get(\App\Application\Services\AuthService::class)));

$app->run();
