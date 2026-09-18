<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Campaign\Campaign;
use PHPUnit\Framework\TestCase;

final class CampaignTest extends TestCase
{
    public function test_it_accepts_a_valid_campaign(): void
    {
        $campaign = new Campaign(
            1,
            'Campanha de Primavera',
            10000,
            0,
            '2026-10-01 00:00:00',
            '2026-10-31 23:59:59',
            'active',
            '2026-09-17 12:00:00',
        );

        self::assertSame(10000, $campaign->budgetTotal);
        self::assertSame('active', $campaign->status);
    }

    public function test_it_rejects_a_non_positive_budget(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Campaign(1, 'Campanha', 0, 0, '2026-10-01 00:00:00', '2026-10-31 23:59:59', 'active', '2026-09-17 12:00:00');
    }

    public function test_it_rejects_a_period_that_does_not_end_after_it_starts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Campaign(1, 'Campanha', 1000, 0, '2026-10-31 23:59:59', '2026-10-01 00:00:00', 'active', '2026-09-17 12:00:00');
    }

    public function test_it_rejects_an_invalid_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Campaign(1, 'Campanha', 1000, 0, 'not-a-date', '2026-10-31 23:59:59', 'active', '2026-09-17 12:00:00');
    }
}
