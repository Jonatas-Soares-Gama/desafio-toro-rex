<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Security\JwtTokenService;
use Firebase\JWT\ExpiredException;
use PHPUnit\Framework\TestCase;

final class JwtTokenServiceTest extends TestCase
{
    private JwtTokenService $service;

    protected function setUp(): void
    {
        $this->service = new JwtTokenService('test-secret-that-is-at-least-32-chars', 3600);
    }

    public function test_it_issues_and_decodes_a_token(): void
    {
        $token = $this->service->issue(7, 'seller');

        self::assertSame(['sub' => 7, 'role' => 'seller'], $this->service->decode($token));
    }

    public function test_it_rejects_a_tampered_token(): void
    {
        $token = $this->service->issue(7, 'seller');
        $tamperedToken = substr($token, 0, -1) . 'x';

        $this->expectException(\Throwable::class);

        $this->service->decode($tamperedToken);
    }
}
