<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class ConnectionFactory
{
    public function create(): PDO
    {
        $host = $this->requiredEnv('DB_HOST');
        $port = $this->requiredEnv('DB_PORT');
        $database = $this->requiredEnv('DB_DATABASE');
        $username = $this->requiredEnv('DB_USERNAME');
        $password = $this->requiredEnv('DB_PASSWORD');

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );

        return $pdo;
    }

    private function requiredEnv(string $name): string
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            throw new \RuntimeException("Missing required environment variable: {$name}");
        }

        return $value;
    }
}
