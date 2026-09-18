<?php

declare(strict_types=1);

namespace App\Application\Campaign;

use App\Domain\Campaign\Campaign;
use App\Infrastructure\Persistence\CampaignRepository;

final class CampaignService
{
    public function __construct(private readonly CampaignRepository $campaigns)
    {
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): Campaign
    {
        [$name, $budgetTotal, $startsAt, $endsAt] = $this->validatedInput($input);

        return $this->campaigns->create($name, $budgetTotal, $startsAt, $endsAt);
    }

    /** @return list<Campaign> */
    public function list(): array
    {
        return $this->campaigns->all();
    }

    /** @return array{0: string, 1: int, 2: string, 3: string} */
    private function validatedInput(array $input): array
    {
        if (array_key_exists('budget_used', $input) || array_key_exists('status', $input)) {
            throw new CampaignValidationException('Campaign managed fields cannot be provided.');
        }

        $name = $input['name'] ?? null;
        $budgetTotal = $input['budget_total'] ?? null;
        $startsAt = $input['starts_at'] ?? null;
        $endsAt = $input['ends_at'] ?? null;

        if (!is_string($name) || !is_int($budgetTotal) || !is_string($startsAt) || !is_string($endsAt)) {
            throw new CampaignValidationException('Campaign fields are invalid.');
        }

        $name = trim($name);

        try {
            Campaign::validateAttributes($name, $budgetTotal, 0, $startsAt, $endsAt, 'active');
        } catch (\InvalidArgumentException $exception) {
            throw new CampaignValidationException($exception->getMessage(), 0, $exception);
        }

        return [$name, $budgetTotal, $startsAt, $endsAt];
    }
}
