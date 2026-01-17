<?php

declare(strict_types=1);

use App\Application\Middleware\CorsMiddleware;
use App\Application\Middleware\JwtAuthMiddleware;
use App\Domain\Auth\JwtService;
use App\Domain\Auth\PasswordService;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repositories\PgUserRepository;
use App\Presentation\Controllers\AuthController;
use App\Presentation\Controllers\UserController;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Load configurations
$jwtConfig = require __DIR__ . '/../config/jwt.php';
$appConfig = require __DIR__ . '/../config/app.php';

// Create container
$container = new DI\Container();
AppFactory::setContainer($container);

// Register services in container
$container->set('jwt_config', $jwtConfig);
$container->set('app_config', $appConfig);

$container->set(PDO::class, function() {
    return Connection::getInstance();
});

$container->set(JwtService::class, function(ContainerInterface $c) {
    return new JwtService($c->get('jwt_config'));
});

$container->set(PasswordService::class, function() {
    return new PasswordService();
});

$container->set(UserRepository::class, function(ContainerInterface $c) {
    return new PgUserRepository($c->get(PDO::class));
});

$container->set(AuthController::class, function(ContainerInterface $c) {
    return new AuthController(
        $c->get(UserRepository::class),
        $c->get(JwtService::class),
        $c->get(PasswordService::class)
    );
});

$container->set(UserController::class, function(ContainerInterface $c) {
    return new UserController($c->get(UserRepository::class));
});

// Create Slim app
$app = AppFactory::create();
$app->addRoutingMiddleware();

// Add global CORS middleware
$app->add(new CorsMiddleware($appConfig['cors']));

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(
    $appConfig['debug'],
    true,
    true
);

// Handle OPTIONS requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Routes
$app->group('/api/v1', function ($app) use ($container) {
    
    // Authentication routes (no auth required)
    $app->group('/auth', function ($app) use ($container) {
        $app->post('/register', [AuthController::class, 'register']);
        $app->post('/login', [AuthController::class, 'login']);
    });

    // User routes (auth required)
    $app->group('/users', function ($app) use ($container) {
        $app->get('/me', [UserController::class, 'me']);
        $app->put('/me', [UserController::class, 'updateMe']);
        $app->get('', [UserController::class, 'list']);
        $app->get('/{id}', [UserController::class, 'getById']);
    })->add(new JwtAuthMiddleware($container->get(JwtService::class)));
    
});

// Health check
$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'timestamp' => date('c')
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
