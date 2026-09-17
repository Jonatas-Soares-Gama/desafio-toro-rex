<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Response\JsonResponse;
use App\Http\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_it_dispatches_a_registered_get_route(): void
    {
        $router = new Router();
        $router->get('/health', static fn() => new JsonResponse(['status' => 'ok']));

        $response = $router->dispatch('GET', '/health');

        self::assertSame(200, $response->statusCode());
        self::assertSame(['status' => 'ok'], $response->payload());
    }

    public function test_it_returns_not_found_for_an_unknown_route(): void
    {
        $response = (new Router())->dispatch('GET', '/unknown');

        self::assertSame(404, $response->statusCode());
        self::assertSame(['error' => 'Route not found'], $response->payload());
    }

    public function test_it_dispatches_a_route_with_a_path_parameter(): void
    {
        $router = new Router();
        $router->put('/products/{id}', static fn(\App\Http\Request $request) => new JsonResponse([
            'id' => $request->pathParameter('id'),
        ]));

        $response = $router->dispatch('PUT', '/products/42');

        self::assertSame(200, $response->statusCode());
        self::assertSame(['id' => '42'], $response->payload());
    }
}
