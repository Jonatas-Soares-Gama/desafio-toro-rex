<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Auth\UserFinder;
use App\Application\User\DuplicateUserEmailException;
use PDO;
use PDOException;

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

    public function isSeller(int $id): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM users WHERE id = :id AND role = \'seller\'');
        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    /** @return list<array{id: int, name: string, email: string, role: string, created_at: string}> */
    public function allSellers(): array
    {
        $statement = $this->connection->query(
            'SELECT id, name, email, role, created_at FROM users WHERE role = \'seller\' ORDER BY name, id',
        );

        return array_map($this->mapPublic(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array{id: int, name: string, email: string, role: string, created_at: string} */
    public function createSeller(string $name, string $email, string $passwordHash): array
    {
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, \'seller\')',
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new DuplicateUserEmailException('User email already exists.', 0, $exception);
            }

            throw $exception;
        }

        $id = (int) $this->connection->lastInsertId();
        $statement = $this->connection->prepare(
            'SELECT id, name, email, role, created_at FROM users WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            throw new \RuntimeException('Created user could not be loaded.');
        }

        return $this->mapPublic($user);
    }

    /** @param array<string, mixed> $row */
    /** @return array{id: int, name: string, email: string, role: string, created_at: string} */
    private function mapPublic(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'role' => (string) $row['role'],
            'created_at' => (string) $row['created_at'],
        ];
    }
}
