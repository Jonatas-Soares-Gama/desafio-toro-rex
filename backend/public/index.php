<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SalesController;
use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Response\JsonResponse;
use App\Http\Routing\Router;
use App\Application\Auth\LoginService;
use App\Application\Product\ProductService;
use App\Application\Campaign\CampaignService;
use App\Application\Sales\SalesService;
use App\Infrastructure\Database\ConnectionFactory;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Persistence\ProductRepository;
use App\Infrastructure\Persistence\CampaignRepository;
use App\Infrastructure\Persistence\SalesRepository;
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
$productController = new ProductController(new ProductService(new ProductRepository($connection)));
$campaignController = new CampaignController(new CampaignService(new CampaignRepository($connection)));
$salesController = new SalesController(new SalesService(
    $connection,
    new SalesRepository($connection),
    new ProductRepository($connection),
    new CampaignRepository($connection),
    new UserRepository($connection),
));

$router->post('/auth/login', static function () use ($loginController) {
    $body = json_decode(file_get_contents('php://input') ?: '{}', true);

    return $loginController(is_array($body) ? $body : []);
});
$router->get(
    '/admin/ping',
    static fn() => new JsonResponse(['status' => 'ok']),
    [$authentication, new RoleMiddleware('admin')],
);
$productMiddleware = [$authentication, new RoleMiddleware('admin')];
$router->post('/products', $productController->create(...), $productMiddleware);
$router->get('/products', $productController->list(...), $productMiddleware);
$router->put('/products/{id}', $productController->update(...), $productMiddleware);
$router->delete('/products/{id}', $productController->delete(...), $productMiddleware);
$router->post('/campaigns', $campaignController->create(...), $productMiddleware);
$router->get('/campaigns', $campaignController->list(...), $productMiddleware);
$router->post('/sales', $salesController->create(...), $productMiddleware);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$headers = function_exists('getallheaders') ? getallheaders() : [];
$ifAuthorization = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
if ($ifAuthorization !== null && !isset($headers['Authorization'])) {
    $headers['Authorization'] = $ifAuthorization;
}
$body = json_decode(file_get_contents('php://input') ?: '{}', true);
$response = $router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $path,
    $headers,
    is_array($body) ? $body : [],
);

http_response_code($response->statusCode());
header('Content-Type: application/json');
echo $response->toJson();
