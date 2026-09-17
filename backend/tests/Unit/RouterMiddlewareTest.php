<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use App\Http\Routing\Router;
use App\Infrastructure\Security\JwtTokenService;
use PHPUnit\Framework\TestCase;

final class RouterMiddlewareTest extends TestCase
{
    private JwtTokenService $tokens;

    protected function setUp(): void
    {
        $this->tokens = new JwtTokenService('test-secret-that-is-at-least-32-chars', 3600);
    }

    public function test_authentication_runs_before_role_authorization(): void
    {
        $router = new Router();
        $router->get(
            '/admin-only',
            static fn(Request $request) => new JsonResponse(['status' => 'ok']),
            [
                new AuthenticationMiddleware($this->tokens),
                new RoleMiddleware('admin'),
            ],
        );

        $unauthenticated = $router->dispatch('GET', '/admin-only');
        $seller = $router->dispatch('GET', '/admin-only', [
            'Authorization' => 'Bearer ' . $this->tokens->issue(7, 'seller'),
        ]);
        $admin = $router->dispatch('GET', '/admin-only', [
            'Authorization' => 'Bearer ' . $this->tokens->issue(1, 'admin'),
        ]);

        self::assertSame(401, $unauthenticated->statusCode());
        self::assertSame(403, $seller->statusCode());
        self::assertSame(200, $admin->statusCode());
    }
}
