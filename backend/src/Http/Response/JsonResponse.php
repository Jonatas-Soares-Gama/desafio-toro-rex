<?php

declare(strict_types=1);

namespace App\Http\Response;

final readonly class JsonResponse
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        private array $payload,
        private int $statusCode = 200,
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    public function toJson(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
