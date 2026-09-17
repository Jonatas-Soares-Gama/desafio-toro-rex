<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Product\DuplicateProductSkuException;
use App\Application\Product\ProductNotFoundException;
use App\Domain\Product\Product;
use PDO;
use PDOException;

final class ProductRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(string $name, string $sku, int $pointsPerUnit): Product
    {
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO products (name, sku, points_per_unit) VALUES (:name, :sku, :points_per_unit)',
            );
            $statement->execute([
                'name' => $name,
                'sku' => $sku,
                'points_per_unit' => $pointsPerUnit,
            ]);
        } catch (PDOException $exception) {
            $this->throwIfDuplicateSku($exception);
            throw $exception;
        }

        return $this->find((int) $this->connection->lastInsertId());
    }

    /** @return list<Product> */
    public function all(): array
    {
        $statement = $this->connection->query(
            'SELECT id, name, sku, points_per_unit, active, created_at FROM products ORDER BY id',
        );

        return array_map($this->map(...), $statement->fetchAll());
    }

    public function update(int $id, string $name, string $sku, int $pointsPerUnit): Product
    {
        $this->find($id);

        try {
            $statement = $this->connection->prepare(
                'UPDATE products SET name = :name, sku = :sku, points_per_unit = :points_per_unit WHERE id = :id',
            );
            $statement->execute([
                'id' => $id,
                'name' => $name,
                'sku' => $sku,
                'points_per_unit' => $pointsPerUnit,
            ]);
        } catch (PDOException $exception) {
            $this->throwIfDuplicateSku($exception);
            throw $exception;
        }

        return $this->find($id);
    }

    public function deactivate(int $id): Product
    {
        $this->find($id);
        $statement = $this->connection->prepare('UPDATE products SET active = FALSE WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $this->find($id);
    }

    private function find(int $id): Product
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, sku, points_per_unit, active, created_at FROM products WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            throw new ProductNotFoundException('Product not found.');
        }

        return $this->map($row);
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Product
    {
        return new Product(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['sku'],
            (int) $row['points_per_unit'],
            (bool) $row['active'],
            (string) $row['created_at'],
        );
    }

    private function throwIfDuplicateSku(PDOException $exception): void
    {
        if (($exception->errorInfo[1] ?? null) === 1062) {
            throw new DuplicateProductSkuException('Product SKU already exists.', 0, $exception);
        }
    }
}
