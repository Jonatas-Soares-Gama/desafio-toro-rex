<?php

declare(strict_types=1);

namespace App\Domain\Sale;

use DateTimeImmutable;
use DateTimeZone;

final readonly class Sale
{
    public static function calculatePoints(int $quantity, int $pointsPerUnit): int
    {
        if ($quantity <= 0 || $pointsPerUnit <= 0) {
            throw new \InvalidArgumentException('Sale points inputs must be positive.');
        }

        return $quantity * $pointsPerUnit;
    }

    public function isWithinCancellationWindow(DateTimeImmutable $now): bool
    {
        $createdAt = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $this->createdAt,
            new DateTimeZone('UTC'),
        );
        $errors = DateTimeImmutable::getLastErrors();

        if ($createdAt === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException('Sale creation date is invalid.');
        }

        return $now < $createdAt->modify('+30 days');
    }

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
