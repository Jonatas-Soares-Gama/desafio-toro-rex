<?php

declare(strict_types=1);

namespace App\Domain\Sale;

final readonly class Sale
{
    public function __construct(
        public int $id,
        public string $externalId,
        public int $campaignId,
        public int $sellerId,
        public int $productId,
        public int $quantity,
        public string $unitValue,
        public string $status,
        public string $createdAt,
    ) {
        if ($this->id <= 0 || $this->campaignId <= 0 || $this->sellerId <= 0 || $this->productId <= 0) {
            throw new \InvalidArgumentException('Sale identifiers must be positive.');
        }

        if ($this->externalId === '' || strlen($this->externalId) > 120 || $this->quantity <= 0) {
            throw new \InvalidArgumentException('Sale attributes are invalid.');
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $this->unitValue)) {
            throw new \InvalidArgumentException('Sale unit value is invalid.');
        }

        if (!in_array($this->status, ['approved', 'canceled'], true)) {
            throw new \InvalidArgumentException('Sale status is invalid.');
        }
    }
}
