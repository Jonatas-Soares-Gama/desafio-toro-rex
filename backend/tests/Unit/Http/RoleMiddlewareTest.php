<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\User\AuthenticatedPrincipal;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use PHPUnit\Framework\TestCase;

final class RoleMiddlewareTest extends TestCase
{
    public function test_it_returns_forbidden_when_the_role_does_not_match(): void
    {
        $request = new Request('GET', '/admin', [], [
            'principal' => new AuthenticatedPrincipal(7, 'seller'),
        ]);

        $response = (new RoleMiddleware('admin'))->handle(
            $request,
            static fn() => new JsonResponse(['status' => 'ok']),
        );

        self::assertSame(403, $response->statusCode());
        self::assertSame(['error' => 'Forbidden'], $response->payload());
    }

    public function test_it_calls_the_next_handler_for_the_required_role(): void
    {
        $request = new Request('GET', '/admin', [], [
            'principal' => new AuthenticatedPrincipal(1, 'admin'),
        ]);

        $response = (new RoleMiddleware('admin'))->handle(
            $request,
            static fn() => new JsonResponse(['status' => 'ok']),
        );

        self::assertSame(200, $response->statusCode());
        self::assertSame(['status' => 'ok'], $response->payload());
    }
}
