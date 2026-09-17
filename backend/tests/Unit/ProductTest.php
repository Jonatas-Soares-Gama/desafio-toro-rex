<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Product\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function test_it_accepts_a_valid_product(): void
    {
        $product = new Product(1, 'Produto A', 'PROD-A', 100, true, '2026-09-17 12:00:00');

        self::assertSame('PROD-A', $product->sku);
        self::assertSame(100, $product->pointsPerUnit);
    }

    public function test_it_rejects_non_positive_points(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Product(1, 'Produto A', 'PROD-A', 0, true, '2026-09-17 12:00:00');
    }

    public function test_it_rejects_an_empty_sku(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Product(1, 'Produto A', '', 100, true, '2026-09-17 12:00:00');
    }
}
