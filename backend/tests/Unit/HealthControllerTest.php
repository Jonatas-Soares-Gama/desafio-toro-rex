<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\HealthController;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    public function test_it_returns_a_healthy_status(): void
    {
        $response = (new HealthController)();

        self::assertSame(200, $response->statusCode());
        self::assertSame(['status' => 'ok'], $response->payload());
    }
}
