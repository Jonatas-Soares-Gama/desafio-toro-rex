<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Routing\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

$router = new Router();
$healthController = new HealthController();
$router->get('/health', static fn() => $healthController());

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$response = $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);

http_response_code($response->statusCode());
header('Content-Type: application/json');
echo $response->toJson();
