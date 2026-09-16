<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class ConnectionFactory
{
    public function create(): PDO
    {
        $host = $this->env('DB_HOST', 'database');
        $port = $this->env('DB_PORT', '3306');
        $database = $this->env('DB_DATABASE', 'toro');
        $username = $this->env('DB_USERNAME', 'toro');
        $password = $this->env('DB_PASSWORD', 'toro');

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

    private function env(string $name, string $default): string
    {
        $value = getenv($name);

        return $value === false || $value === '' ? $default : $value;
    }
}
