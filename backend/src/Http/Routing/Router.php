<?php

declare(strict_types=1);

namespace App\Http\Routing;

use App\Http\Middleware\Middleware;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use Closure;

final class Router
{
    /** @var array<string, list<array{path: string, handler: Closure, middleware: list<Middleware>}>> */
    private array $routes = [];

    /** @param Closure(Request): JsonResponse $handler */
    /** @param list<Middleware> $middleware */
    public function get(string $path, Closure $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /** @param Closure(Request): JsonResponse $handler */
    /** @param list<Middleware> $middleware */
    public function post(string $path, Closure $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /** @param Closure(Request): JsonResponse $handler */
    /** @param list<Middleware> $middleware */
    public function put(string $path, Closure $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /** @param Closure(Request): JsonResponse $handler */
    /** @param list<Middleware> $middleware */
    public function delete(string $path, Closure $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /** @param array<string, string> $headers */
    /** @param array<string, mixed> $body */
    public function dispatch(string $method, string $path, array $headers = [], array $body = []): JsonResponse
    {
        $route = null;
        $pathParameters = [];

        foreach ($this->routes[$method] ?? [] as $candidate) {
            $parameters = $this->matchPath($candidate['path'], $path);

            if ($parameters !== null) {
                $route = $candidate;
                $pathParameters = $parameters;
                break;
            }
        }

        if ($route === null) {
            return new JsonResponse(['error' => 'Route not found'], 404);
        }

        $request = new Request($method, $path, $headers, ['path_parameters' => $pathParameters], $body);
        $next = $route['handler'];

        foreach (array_reverse($route['middleware']) as $middleware) {
            $next = static fn(Request $request): JsonResponse => $middleware->handle($request, $next);
        }

        return $next($request);
    }

    /** @param Closure(Request): JsonResponse $handler */
    /** @param list<Middleware> $middleware */
    private function addRoute(string $method, string $path, Closure $handler, array $middleware): void
    {
        $this->routes[$method][] = ['path' => $path, 'handler' => $handler, 'middleware' => $middleware];
    }

    /** @return array<string, string>|null */
    private function matchPath(string $routePath, string $requestPath): ?array
    {
        $routeSegments = trim($routePath, '/') === '' ? [] : explode('/', trim($routePath, '/'));
        $requestSegments = trim($requestPath, '/') === '' ? [] : explode('/', trim($requestPath, '/'));

        if (count($routeSegments) !== count($requestSegments)) {
            return null;
        }

        $parameters = [];

        foreach ($routeSegments as $index => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                $parameters[substr($segment, 1, -1)] = rawurldecode($requestSegments[$index]);
                continue;
            }

            if ($segment !== $requestSegments[$index]) {
                return null;
            }
        }

        return $parameters;
    }
}
