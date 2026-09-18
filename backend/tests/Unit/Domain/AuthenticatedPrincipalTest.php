<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\User\AuthenticatedPrincipal;
use PHPUnit\Framework\TestCase;

final class AuthenticatedPrincipalTest extends TestCase
{
    public function test_it_accepts_supported_identity_data(): void
    {
        $principal = new AuthenticatedPrincipal(7, 'seller');

        self::assertSame(7, $principal->userId);
        self::assertSame('seller', $principal->role);
    }

    public function test_it_rejects_an_unknown_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AuthenticatedPrincipal(7, 'manager');
    }

    public function test_it_rejects_a_non_positive_user_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AuthenticatedPrincipal(0, 'seller');
    }
}
