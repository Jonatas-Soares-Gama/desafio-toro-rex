<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Auth\UserFinder;
use PDO;

final class UserRepository implements UserFinder
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array{id: int, password_hash: string, role: string}|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, password_hash, role FROM users WHERE email = :email LIMIT 1',
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : [
            'id' => (int) $user['id'],
            'password_hash' => (string) $user['password_hash'],
            'role' => (string) $user['role'],
        ];
    }
}
