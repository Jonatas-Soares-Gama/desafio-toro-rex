<?php

declare(strict_types=1);

namespace App\Application\Auth;

interface UserFinder
{
    /** @return array{id: int, password_hash: string, role: string}|null */
    public function findByEmail(string $email): ?array;
}
