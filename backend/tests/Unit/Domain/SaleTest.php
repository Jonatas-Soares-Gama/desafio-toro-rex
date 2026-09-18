<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Sale\Sale;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class SaleTest extends TestCase
{
    public function test_it_calculates_points_from_quantity_and_product_rule(): void
    {
        self::assertSame(300, Sale::calculatePoints(3, 100));
    }

    public function test_it_rejects_non_positive_point_inputs(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Sale::calculatePoints(0, 100);
    }

    public function test_it_accepts_a_valid_sale(): void
    {
        $sale = new Sale(
            1,
            'erp-sale-1001',
            1,
            2,
            1,
            3,
            '149.90',
            'approved',
            '2026-09-17 12:00:00',
        );

        self::assertSame('erp-sale-1001', $sale->externalId);
        self::assertSame('149.90', $sale->unitValue);
    }

    public function test_it_rejects_an_invalid_unit_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Sale(1, 'erp-sale-1001', 1, 2, 1, 3, '149.999', 'approved', '2026-09-17 12:00:00');
    }

    public function test_it_allows_cancellation_before_thirty_days(): void
    {
        $sale = $this->saleCreatedAt('2026-09-01 12:00:00');

        self::assertTrue($sale->isWithinCancellationWindow($this->utc('2026-10-01 11:59:59')));
    }

    public function test_it_rejects_cancellation_at_or_after_thirty_days(): void
    {
        $sale = $this->saleCreatedAt('2026-09-01 12:00:00');

        self::assertFalse($sale->isWithinCancellationWindow($this->utc('2026-10-01 12:00:00')));
        self::assertFalse($sale->isWithinCancellationWindow($this->utc('2026-10-02 12:00:00')));
    }

    private function saleCreatedAt(string $createdAt): Sale
    {
        return new Sale(1, 'erp-sale-1001', 1, 2, 1, 3, '149.90', 'approved', $createdAt);
    }

    private function utc(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
