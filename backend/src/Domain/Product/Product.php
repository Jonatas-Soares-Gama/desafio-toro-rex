<?php

declare(strict_types=1);

namespace App\Domain\Product;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $name,
        public string $sku,
        public int $pointsPerUnit,
        public bool $active,
        public string $createdAt,
    ) {
        self::validateAttributes($this->name, $this->sku, $this->pointsPerUnit);

        if ($this->id <= 0) {
            throw new \InvalidArgumentException('Product ID must be positive.');
        }
    }

    public static function validateAttributes(string $name, string $sku, int $pointsPerUnit): void
    {
        if ($name === '' || strlen($name) > 160) {
            throw new \InvalidArgumentException('Product name is invalid.');
        }

        if ($sku === '' || strlen($sku) > 80) {
            throw new \InvalidArgumentException('Product SKU is invalid.');
        }

        if ($pointsPerUnit <= 0) {
            throw new \InvalidArgumentException('Product points must be positive.');
        }
    }
}
