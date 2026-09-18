<?php

declare(strict_types=1);

namespace App\Application\Sales;

use App\Application\Campaign\CampaignNotFoundException;
use App\Application\Product\ProductNotFoundException;
use App\Domain\Sale\Sale;
use App\Infrastructure\Persistence\CampaignRepository;
use App\Infrastructure\Persistence\ProductRepository;
use App\Infrastructure\Persistence\SalesRepository;
use App\Infrastructure\Persistence\UserRepository;
use PDO;
use PDOException;

final class SalesService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly SalesRepository $sales,
        private readonly ProductRepository $products,
        private readonly CampaignRepository $campaigns,
        private readonly UserRepository $users,
    ) {
    }

    /** @param array<string, mixed> $input */
    /** @return array{sale: Sale, created: bool} */
    public function create(array $input): array
    {
        [$externalId, $campaignId, $sellerId, $productId, $quantity, $unitValue] = $this->validatedInput($input);

        $this->connection->beginTransaction();

        try {
            $existing = $this->sales->findByExternalIdForUpdate($externalId);
            if ($existing !== null) {
                if (!$this->matches($existing, $campaignId, $sellerId, $productId, $quantity, $unitValue)) {
                    $this->connection->rollBack();
                    throw new SaleConflictException('Sale external_id is already used with different data.');
                }

                $this->connection->commit();

                return ['sale' => $existing, 'created' => false];
            }

            try {
                $product = $this->products->find($productId);
            } catch (ProductNotFoundException $exception) {
                throw new SaleValidationException('Product not found.', 0, $exception);
            }
            if (!$product->active) {
                throw new SaleValidationException('Product is inactive.');
            }

            if (!$this->users->isSeller($sellerId)) {
                throw new SaleValidationException('Seller not found.');
            }

            $campaign = $this->campaigns->findForUpdate($campaignId);
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            if ($campaign->status !== 'active' || $now < new \DateTimeImmutable($campaign->startsAt, new \DateTimeZone('UTC')) || $now >= new \DateTimeImmutable($campaign->endsAt, new \DateTimeZone('UTC'))) {
                throw new SaleValidationException('Campaign is not available.');
            }

            $points = $quantity * $product->pointsPerUnit;
            if ($campaign->budgetUsed + $points > $campaign->budgetTotal) {
                throw new SaleValidationException('Campaign budget is insufficient.');
            }

            $sale = $this->sales->create($externalId, $campaignId, $sellerId, $productId, $quantity, $unitValue);
            $this->sales->createCredit($sale, $points);
            $this->campaigns->increaseBudgetUsed($campaignId, $points);
            $this->connection->commit();

            return ['sale' => $sale, 'created' => true];
        } catch (SaleConflictException|SaleValidationException $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        } catch (CampaignNotFoundException $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw new SaleValidationException('Campaign not found.', 0, $exception);
        } catch (PDOException $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            if (($exception->errorInfo[1] ?? null) === 1062) {
                $existing = $this->sales->findByExternalId($externalId);
                if ($existing !== null && $this->matches($existing, $campaignId, $sellerId, $productId, $quantity, $unitValue)) {
                    return ['sale' => $existing, 'created' => false];
                }

                throw new SaleConflictException('Sale external_id is already used.', 0, $exception);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array{0: string, 1: int, 2: int, 3: int, 4: int, 5: string} */
    private function validatedInput(array $input): array
    {
        $externalId = $input['external_id'] ?? null;
        $campaignId = $input['campaign_id'] ?? null;
        $sellerId = $input['seller_id'] ?? null;
        $productId = $input['product_id'] ?? null;
        $quantity = $input['quantity'] ?? null;
        $unitValue = $input['unit_value'] ?? null;

        if (!is_string($externalId) || !is_int($campaignId) || !is_int($sellerId) || !is_int($productId) || !is_int($quantity) || (!is_string($unitValue) && !is_int($unitValue))) {
            throw new SaleValidationException('Sale fields are invalid.');
        }

        $externalId = trim($externalId);
        $unitValue = $this->normalizeMoney((string) $unitValue);
        if ($externalId === '' || strlen($externalId) > 120 || $campaignId <= 0 || $sellerId <= 0 || $productId <= 0 || $quantity <= 0 || $unitValue === null) {
            throw new SaleValidationException('Sale fields are invalid.');
        }

        return [$externalId, $campaignId, $sellerId, $productId, $quantity, $unitValue];
    }

    private function normalizeMoney(string $value): ?string
    {
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        $whole = ltrim($whole, '0');

        return ($whole === '' ? '0' : $whole) . '.' . str_pad($fraction, 2, '0');
    }

    private function matches(Sale $sale, int $campaignId, int $sellerId, int $productId, int $quantity, string $unitValue): bool
    {
        return $sale->campaignId === $campaignId
            && $sale->sellerId === $sellerId
            && $sale->productId === $productId
            && $sale->quantity === $quantity
            && $sale->unitValue === $unitValue;
    }
}
