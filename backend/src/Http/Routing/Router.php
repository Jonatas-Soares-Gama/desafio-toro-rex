<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Response\JsonResponse;
use Closure;

final class Router
{
    /** @var array<string, array<string, Closure(): JsonResponse>> */
    private array $routes = [];

    /** @param Closure(): JsonResponse $handler */
    public function get(string $path, Closure $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /** @param Closure(): JsonResponse $handler */
    public function post(string $path, Closure $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): JsonResponse
    {
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            return new JsonResponse(['error' => 'Route not found'], 404);
        }

        return $handler();
    }
}
