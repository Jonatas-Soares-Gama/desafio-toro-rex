<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\LoginController;
use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Response\JsonResponse;
use App\Http\Routing\Router;
use App\Application\Auth\LoginService;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Security\JwtTokenService;

require dirname(__DIR__) . '/vendor/autoload.php';

$router = new Router();
$healthController = new HealthController();
$router->get('/health', static fn() => $healthController());

$connection = (new ConnectionFactory())->create();

$jwtSecret = getenv('JWT_SECRET') ?: 'development-secret-change-me-32-chars-min';

$loginController = new LoginController(
    new LoginService(
        new UserRepository($connection),
        new JwtTokenService($jwtSecret, 3600),
    ),
);
$authentication = new AuthenticationMiddleware(new JwtTokenService($jwtSecret, 3600));

$router->post('/auth/login', static function () use ($loginController) {
    $body = json_decode(file_get_contents('php://input') ?: '{}', true);

    return $loginController(is_array($body) ? $body : []);
});
$router->get(
    '/admin/ping',
    static fn() => new JsonResponse(['status' => 'ok']),
    [$authentication, new RoleMiddleware('admin')],
);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$headers = function_exists('getallheaders') ? getallheaders() : [];
$response = $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path, $headers);

http_response_code($response->statusCode());
header('Content-Type: application/json');
echo $response->toJson();
