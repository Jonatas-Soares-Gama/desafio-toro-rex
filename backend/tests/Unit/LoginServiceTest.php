<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Auth\AuthenticationException;
use App\Application\Auth\LoginService;
use App\Application\Auth\UserFinder;
use App\Infrastructure\Security\JwtTokenService;
use PHPUnit\Framework\TestCase;

final class LoginServiceTest extends TestCase
{
    public function test_it_returns_a_token_for_valid_credentials(): void
    {
        $repository = $this->createMock(UserFinder::class);
        $repository->method('findByEmail')->willReturn([
            'id' => 7,
            'password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            'role' => 'seller',
        ]);
        $tokens = new JwtTokenService('test-secret-that-is-at-least-32-chars', 3600);

        $token = (new LoginService($repository, $tokens))->execute('seller@example.com', 'secret');

        self::assertSame(['sub' => 7, 'role' => 'seller'], $tokens->decode($token));
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        $repository = $this->createMock(UserFinder::class);
        $repository->method('findByEmail')->willReturn(null);

        $this->expectException(AuthenticationException::class);

        (new LoginService($repository, new JwtTokenService('test-secret-that-is-at-least-32-chars', 3600)))
            ->execute('unknown@example.com', 'secret');
    }
}
