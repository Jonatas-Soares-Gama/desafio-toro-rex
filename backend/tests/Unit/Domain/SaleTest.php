<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Sale\Sale;
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
}
