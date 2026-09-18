<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Campaign\CampaignNotFoundException;
use App\Domain\Campaign\Campaign;
use PDO;

final class CampaignRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(string $name, int $budgetTotal, string $startsAt, string $endsAt): Campaign
    {
        $statement = $this->connection->prepare(
            'INSERT INTO campaigns (name, budget_total, budget_used, starts_at, ends_at, status)
             VALUES (:name, :budget_total, 0, :starts_at, :ends_at, \'active\')',
        );
        $statement->execute([
            'name' => $name,
            'budget_total' => $budgetTotal,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return $this->find((int) $this->connection->lastInsertId());
    }

    /** @return list<Campaign> */
    public function all(): array
    {
        $statement = $this->connection->query(
            'SELECT id, name, budget_total, budget_used, starts_at, ends_at, status, created_at
             FROM campaigns ORDER BY id',
        );

        return array_map($this->map(...), $statement->fetchAll());
    }

    public function findForUpdate(int $id): Campaign
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, budget_total, budget_used, starts_at, ends_at, status, created_at
             FROM campaigns WHERE id = :id FOR UPDATE',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            throw new CampaignNotFoundException('Campaign not found.');
        }

        return $this->map($row);
    }

    public function increaseBudgetUsed(int $id, int $points): void
    {
        $statement = $this->connection->prepare(
            'UPDATE campaigns SET budget_used = budget_used + :points WHERE id = :id',
        );
        $statement->execute(['id' => $id, 'points' => $points]);
    }

    public function decreaseBudgetUsed(int $id, int $points): void
    {
        $statement = $this->connection->prepare(
            'UPDATE campaigns
             SET budget_used = budget_used - :decrease_points
             WHERE id = :id AND budget_used >= :minimum_points',
        );
        $statement->execute([
            'id' => $id,
            'decrease_points' => $points,
            'minimum_points' => $points,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('Campaign budget cannot be decreased.');
        }
    }

    private function find(int $id): Campaign
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, budget_total, budget_used, starts_at, ends_at, status, created_at
             FROM campaigns WHERE id = :id',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            throw new \RuntimeException('Created campaign could not be loaded.');
        }

        return $this->map($row);
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Campaign
    {
        return new Campaign(
            (int) $row['id'],
            (string) $row['name'],
            (int) $row['budget_total'],
            (int) $row['budget_used'],
            (string) $row['starts_at'],
            (string) $row['ends_at'],
            (string) $row['status'],
            (string) $row['created_at'],
        );
    }
}
