<?php

declare(strict_types=1);

namespace App\Domain\Campaign;

use DateTimeImmutable;
use DateTimeZone;

final readonly class Campaign
{
    public function __construct(
        public int $id,
        public string $name,
        public int $budgetTotal,
        public int $budgetUsed,
        public string $startsAt,
        public string $endsAt,
        public string $status,
        public string $createdAt,
    ) {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('Campaign ID must be positive.');
        }

        self::validateAttributes(
            $this->name,
            $this->budgetTotal,
            $this->budgetUsed,
            $this->startsAt,
            $this->endsAt,
            $this->status,
        );
    }

    public static function validateAttributes(
        string $name,
        int $budgetTotal,
        int $budgetUsed,
        string $startsAt,
        string $endsAt,
        string $status,
    ): void {
        if ($name === '' || strlen($name) > 160) {
            throw new \InvalidArgumentException('Campaign name is invalid.');
        }

        if ($budgetTotal <= 0 || $budgetUsed < 0 || $budgetUsed > $budgetTotal) {
            throw new \InvalidArgumentException('Campaign budget is invalid.');
        }

        if (!in_array($status, ['active', 'closed'], true)) {
            throw new \InvalidArgumentException('Campaign status is invalid.');
        }

        $start = self::parseDate($startsAt);
        $end = self::parseDate($endsAt);

        if ($end <= $start) {
            throw new \InvalidArgumentException('Campaign period is invalid.');
        }
    }

    private static function parseDate(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $value,
            new DateTimeZone('UTC'),
        );
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException('Campaign date is invalid.');
        }

        return $date;
    }
}
