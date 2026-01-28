<?php

declare(strict_types=1);

use App\Application\Middleware\CorsMiddleware;
use App\Application\Middleware\JwtAuthMiddleware;
use App\Application\Middleware\RequireRoleMiddleware;
use App\Domain\Auth\JwtService;
use App\Domain\Auth\PasswordService;
use App\Domain\Auth\RefreshTokenRepository;
use App\Domain\Auth\PasswordResetRepository;
use App\Domain\Notification\MailService;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repositories\PgUserRepository;
use App\Infrastructure\Repositories\PgRefreshTokenRepository;
use App\Infrastructure\Repositories\PgPasswordResetRepository;
use App\Infrastructure\Mail\SmtpMailService;
use App\Presentation\Controllers\AuthController;
use App\Presentation\Controllers\UserController;
use App\Shared\Response\JsonResponse;
use DI\Container;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Throwable;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Load configurations
$jwtConfig = require __DIR__ . '/../config/jwt.php';
$appConfig = require __DIR__ . '/../config/app.php';
$mailConfig = require __DIR__ . '/../config/mail.php';

// Create container
$container = new Container();
AppFactory::setContainer($container);

// Register services in container
$container->set('jwt_config', $jwtConfig);
$container->set('app_config', $appConfig);
$container->set('mail_config', $mailConfig);

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

$container->set(RefreshTokenRepository::class, function(ContainerInterface $c) {
    return new PgRefreshTokenRepository(
        $c->get(PDO::class),
        $c->get(PasswordService::class)
    );
});

$container->set(PasswordResetRepository::class, function(ContainerInterface $c) {
    return new PgPasswordResetRepository(
        $c->get(PDO::class),
        $c->get(PasswordService::class)
    );
});

$container->set(MailService::class, function(ContainerInterface $c) {
    return new SmtpMailService(
        $c->get('mail_config'),
        $c->get('app_config')['url'] ?? 'http://localhost:8080'
    );
});

$container->set(AuthController::class, function(ContainerInterface $c) {
    return new AuthController(
        $c->get(UserRepository::class),
        $c->get(JwtService::class),
        $c->get(PasswordService::class),
        $c->get(RefreshTokenRepository::class),
        $c->get(PasswordResetRepository::class),
        $c->get(MailService::class),
        $c->get('app_config')
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

$responseFactory = $app->getResponseFactory();

$errorMiddleware->setDefaultErrorHandler(function (
    $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($responseFactory) {
    $response = $responseFactory->createResponse();

    if ($exception instanceof HttpNotFoundException) {
        return JsonResponse::notFound($response, 'Route not found');
    }

    if ($exception instanceof HttpMethodNotAllowedException) {
        return JsonResponse::error(
            $response,
            'METHOD_NOT_ALLOWED',
            'HTTP method not allowed',
            ['allowed_methods' => $exception->getAllowedMethods()],
            405
        );
    }

    $message = $displayErrorDetails ? $exception->getMessage() : 'Something went wrong';

    return JsonResponse::error(
        $response,
        'SERVER_ERROR',
        $message,
        null,
        500
    );
});

// Handle OPTIONS requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Root route
$app->get('/', function ($request, $response) use ($appConfig) {
    return JsonResponse::success($response, [
        'name' => 'Bangladesh Auth API',
        'version' => 'v1',
        'status' => 'ok',
        'base_url' => $appConfig['url'] ?? 'http://localhost:8080'
    ]);
});

// Routes
$app->group('/api/v1', function ($app) use ($container) {
    
    // Authentication routes (no auth required)
    $app->group('/auth', function ($app) use ($container) {
        $app->post('/register', [AuthController::class, 'register']);
        $app->post('/login', [AuthController::class, 'login']);
        $app->post('/refresh', [AuthController::class, 'refresh']);
        $app->post('/logout', [AuthController::class, 'logout']);
        $app->post('/forgot-password', [AuthController::class, 'forgotPassword']);
        $app->post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // User routes (auth required)
    $app->group('/users', function ($app) use ($container) {
        $app->get('/me', [UserController::class, 'me']);
        $app->put('/me', [UserController::class, 'updateMe']);

        $app->get('', [UserController::class, 'list'])
            ->add(new RequireRoleMiddleware(['admin']));

        $app->get('/{id}', [UserController::class, 'getById'])
            ->add(new RequireRoleMiddleware(['admin']));
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
