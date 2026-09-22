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
    private const MAX_DEADLOCK_RETRIES = 2;

    public function __construct(
        private readonly PDO $connection,
        private readonly SalesRepository $sales,
        private readonly ProductRepository $products,
        private readonly CampaignRepository $campaigns,
        private readonly UserRepository $users,
    ) {
    }

    /** @param array<string, mixed> $input */
    /** @return array{sale: Sale, created: bool, points: int} */
    public function create(array $input): array
    {
        return $this->createAttempt($input, 0);
    }

    /** @param array<string, mixed> $input */
    /** @return array{sale: Sale, created: bool, points: int} */
    private function createAttempt(array $input, int $attempt): array
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

                $points = $this->creditPoints($existing);
                $this->connection->commit();

                return ['sale' => $existing, 'created' => false, 'points' => $points];
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

            $points = Sale::calculatePoints($quantity, $product->pointsPerUnit);
            if ($campaign->budgetUsed + $points > $campaign->budgetTotal) {
                throw new SaleValidationException('Campaign budget is insufficient.');
            }

            $sale = $this->sales->create($externalId, $campaignId, $sellerId, $productId, $quantity, $unitValue);
            $this->sales->createCredit($sale, $points);
            $this->campaigns->increaseBudgetUsed($campaignId, $points);
            $this->connection->commit();

            return ['sale' => $sale, 'created' => true, 'points' => $points];
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

            if ($this->isDeadlock($exception)) {
                if ($attempt < self::MAX_DEADLOCK_RETRIES) {
                    $this->waitBeforeRetry($attempt);

                    return $this->createAttempt($input, $attempt + 1);
                }

                throw new SaleConcurrencyException('Sale could not be processed because of concurrent updates.', 0, $exception);
            }

            if (($exception->errorInfo[1] ?? null) === 1062) {
                $existing = $this->sales->findByExternalId($externalId);
                if ($existing !== null && $this->matches($existing, $campaignId, $sellerId, $productId, $quantity, $unitValue)) {
                    return ['sale' => $existing, 'created' => false, 'points' => $this->creditPoints($existing)];
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

    /** @return array{sale: Sale, reversedPoints: int} */
    public function cancel(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '' || strlen($externalId) > 120) {
            throw new SaleValidationException('Sale external_id is invalid.');
        }

        return $this->cancelAttempt($externalId, 0);
    }

    /** @return array{sale: Sale, reversedPoints: int} */
    private function cancelAttempt(string $externalId, int $attempt): array
    {
        $this->connection->beginTransaction();

        try {
            $sale = $this->sales->findByExternalIdForUpdate($externalId);
            if ($sale === null) {
                throw new SaleNotFoundException('Sale not found.');
            }

            if ($sale->status === 'canceled') {
                $points = $this->creditPoints($sale);

                $this->connection->commit();

                return ['sale' => $sale, 'reversedPoints' => $points];
            }

            try {
                $withinWindow = $sale->isWithinCancellationWindow(
                    new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                );
            } catch (\InvalidArgumentException $exception) {
                throw new SaleValidationException('Sale creation date is invalid.', 0, $exception);
            }

            if (!$withinWindow) {
                throw new SaleValidationException('Sale cancellation window has expired.');
            }

            $points = $this->creditPoints($sale);

            $campaign = $this->campaigns->findForUpdate($sale->campaignId);
            if ($campaign->budgetUsed < $points) {
                throw new SaleValidationException('Campaign budget is inconsistent.');
            }

            $canceledSale = $this->sales->markCanceled($sale->id);
            $this->sales->createDebit($canceledSale, $points);
            $this->campaigns->decreaseBudgetUsed($sale->campaignId, $points);
            $this->connection->commit();

            return ['sale' => $canceledSale, 'reversedPoints' => $points];
        } catch (SaleNotFoundException|SaleValidationException $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        } catch (CampaignNotFoundException $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw new SaleValidationException('Campaign not found.', 0, $exception);
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            if ($exception instanceof PDOException && $this->isDeadlock($exception)) {
                if ($attempt < self::MAX_DEADLOCK_RETRIES) {
                    $this->waitBeforeRetry($attempt);

                    return $this->cancelAttempt($externalId, $attempt + 1);
                }

                throw new SaleConcurrencyException('Sale could not be canceled because of concurrent updates.', 0, $exception);
            }

            throw $exception;
        }
    }

    /** @return list<array{id: int, external_id: string, campaign_id: int, campaign_name: string, seller_id: int, seller_name: string, product_id: int, product_name: string, quantity: int, unit_value: string, points: int, status: string, created_at: string}> */
    public function list(): array
    {
        return $this->sales->allWithContext();
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

    private function creditPoints(Sale $sale): int
    {
        $points = $this->sales->findCreditPoints($sale->id);
        if ($points === null || $points <= 0) {
            throw new SaleValidationException('Sale credit is invalid.');
        }

        return $points;
    }

    private function isDeadlock(PDOException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return $sqlState === '40001'
            || (int) ($exception->errorInfo[1] ?? 0) === 1213;
    }

    private function waitBeforeRetry(int $attempt): void
    {
        usleep(10_000 * ($attempt + 1));
    }
}
