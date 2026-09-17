<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Middleware\Middleware;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use Closure;

final class Router
{
    /** @var array<string, array<string, array{handler: Closure, middleware: list<Middleware>}>> */
    private array $routes = [];

    /** @param Closure(Request): JsonResponse $handler */
    /** @param list<Middleware> $middleware */
    public function get(string $path, Closure $handler, array $middleware = []): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /** @param Closure(Request): JsonResponse $handler */
    /** @param list<Middleware> $middleware */
    public function post(string $path, Closure $handler, array $middleware = []): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /** @param array<string, string> $headers */
    public function dispatch(string $method, string $path, array $headers = []): JsonResponse
    {
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            return new JsonResponse(['error' => 'Route not found'], 404);
        }

        $request = new Request($method, $path, $headers);
        $next = $route['handler'];

        foreach (array_reverse($route['middleware']) as $middleware) {
            $next = static fn(Request $request): JsonResponse => $middleware->handle($request, $next);
        }

        return $next($request);
    }
}
