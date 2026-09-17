<?php

declare(strict_types=1);

namespace App\Domain\User;

final readonly class AuthenticatedPrincipal
{
    public function __construct(
        public int $userId,
        public string $role,
    ) {
        if ($this->userId <= 0) {
            throw new \InvalidArgumentException('Authenticated user ID must be positive.');
        }

        if (!in_array($this->role, ['admin', 'seller'], true)) {
            throw new \InvalidArgumentException('Authenticated role is invalid.');
        }
    }
}
