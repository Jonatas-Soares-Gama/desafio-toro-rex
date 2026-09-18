<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use App\Infrastructure\Security\JwtTokenService;
use PHPUnit\Framework\TestCase;

final class AuthenticationMiddlewareTest extends TestCase
{
    private JwtTokenService $tokens;

    protected function setUp(): void
    {
        $this->tokens = new JwtTokenService('test-secret-that-is-at-least-32-chars', 3600);
    }

    public function test_it_rejects_a_missing_authorization_header(): void
    {
        $response = (new AuthenticationMiddleware($this->tokens))->handle(
            new Request('GET', '/private'),
            static fn() => new JsonResponse(['status' => 'ok']),
        );

        self::assertSame(401, $response->statusCode());
        self::assertSame(['error' => 'Unauthenticated'], $response->payload());
    }

    public function test_it_attaches_the_principal_for_a_valid_bearer_token(): void
    {
        $request = new Request('GET', '/private', [
            'Authorization' => 'Bearer ' . $this->tokens->issue(7, 'seller'),
        ]);

        $response = (new AuthenticationMiddleware($this->tokens))->handle(
            $request,
            static function (Request $request): JsonResponse {
                return new JsonResponse([
                    'user_id' => $request->attribute('principal')->userId,
                    'role' => $request->attribute('principal')->role,
                ]);
            },
        );

        self::assertSame(200, $response->statusCode());
        self::assertSame(['user_id' => 7, 'role' => 'seller'], $response->payload());
    }

    public function test_it_accepts_the_bearer_scheme_case_insensitively(): void
    {
        $request = new Request('GET', '/private', [
            'authorization' => 'bearer ' . $this->tokens->issue(7, 'seller'),
        ]);

        $response = (new AuthenticationMiddleware($this->tokens))->handle(
            $request,
            static fn() => new JsonResponse(['status' => 'ok']),
        );

        self::assertSame(200, $response->statusCode());
    }

    public function test_it_rejects_an_invalid_token_without_exposing_details(): void
    {
        $response = (new AuthenticationMiddleware($this->tokens))->handle(
            new Request('GET', '/private', ['Authorization' => 'Bearer invalid']),
            static fn() => new JsonResponse(['status' => 'ok']),
        );

        self::assertSame(401, $response->statusCode());
        self::assertSame(['error' => 'Unauthenticated'], $response->payload());
    }
}
