<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Sale\Sale;
use PDO;

final class SalesRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findByExternalId(string $externalId): ?Sale
    {
        return $this->find($externalId, false);
    }

    public function findByExternalIdForUpdate(string $externalId): ?Sale
    {
        return $this->find($externalId, true);
    }

    public function create(string $externalId, int $campaignId, int $sellerId, int $productId, int $quantity, string $unitValue): Sale
    {
        $statement = $this->connection->prepare(
            'INSERT INTO sales (external_id, campaign_id, seller_id, product_id, quantity, unit_value, status)
             VALUES (:external_id, :campaign_id, :seller_id, :product_id, :quantity, :unit_value, \'approved\')',
        );
        $statement->execute([
            'external_id' => $externalId,
            'campaign_id' => $campaignId,
            'seller_id' => $sellerId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_value' => $unitValue,
        ]);

        return $this->findById((int) $this->connection->lastInsertId());
    }

    public function createCredit(Sale $sale, int $points): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO wallet_entries (seller_id, campaign_id, sale_id, type, points, description)
             VALUES (:seller_id, :campaign_id, :sale_id, \'credit\', :points, :description)',
        );
        $statement->execute([
            'seller_id' => $sale->sellerId,
            'campaign_id' => $sale->campaignId,
            'sale_id' => $sale->id,
            'points' => $points,
            'description' => 'Sale ' . $sale->externalId,
        ]);
    }

    public function findCreditPoints(int $saleId): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT points FROM wallet_entries
             WHERE sale_id = :sale_id AND type = \'credit\' FOR UPDATE',
        );
        $statement->execute(['sale_id' => $saleId]);
        $points = $statement->fetchColumn();

        return $points === false ? null : (int) $points;
    }

    public function markCanceled(int $saleId): Sale
    {
        $statement = $this->connection->prepare(
            'UPDATE sales SET status = \'canceled\'
             WHERE id = :id AND status = \'approved\'',
        );
        $statement->execute(['id' => $saleId]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('Sale could not be canceled.');
        }

        return $this->findById($saleId);
    }

    public function createDebit(Sale $sale, int $points): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO wallet_entries (seller_id, campaign_id, sale_id, type, points, description)
             VALUES (:seller_id, :campaign_id, :sale_id, \'debit\', :points, :description)',
        );
        $statement->execute([
            'seller_id' => $sale->sellerId,
            'campaign_id' => $sale->campaignId,
            'sale_id' => $sale->id,
            'points' => $points,
            'description' => 'Cancellation of sale ' . $sale->externalId,
        ]);
    }

    /** @return list<array{id: int, campaign_id: int, sale_id: int, type: string, points: int, description: string, created_at: string}> */
    public function findWalletEntries(int $sellerId): array
    {
        if ($sellerId <= 0) {
            throw new \InvalidArgumentException('Seller ID must be positive.');
        }

        $statement = $this->connection->prepare(
            'SELECT id, campaign_id, sale_id, type, points, description, created_at
             FROM wallet_entries
             WHERE seller_id = :seller_id
             ORDER BY created_at DESC, id DESC',
        );
        $statement->execute(['seller_id' => $sellerId]);

        return array_map(
            static fn(array $row): array => [
                'id' => (int) $row['id'],
                'campaign_id' => (int) $row['campaign_id'],
                'sale_id' => (int) $row['sale_id'],
                'type' => (string) $row['type'],
                'points' => (int) $row['points'],
                'description' => (string) $row['description'],
                'created_at' => (string) $row['created_at'],
            ],
            $statement->fetchAll(),
        );
    }

    private function find(string $externalId, bool $forUpdate): ?Sale
    {
        $sql = 'SELECT id, external_id, campaign_id, seller_id, product_id, quantity, unit_value, status, created_at
                FROM sales WHERE external_id = :external_id';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute(['external_id' => $externalId]);
        $row = $statement->fetch();

        return $row === false ? null : $this->map($row);
    }

    private function findById(int $id): Sale
    {
        $statement = $this->connection->prepare(
            'SELECT id, external_id, campaign_id, seller_id, product_id, quantity, unit_value, status, created_at
             FROM sales WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            throw new \RuntimeException('Created sale could not be loaded.');
        }

        return $this->map($row);
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Sale
    {
        return new Sale(
            (int) $row['id'],
            (string) $row['external_id'],
            (int) $row['campaign_id'],
            (int) $row['seller_id'],
            (int) $row['product_id'],
            (int) $row['quantity'],
            (string) $row['unit_value'],
            (string) $row['status'],
            (string) $row['created_at'],
        );
    }
}
