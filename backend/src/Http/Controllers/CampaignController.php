<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Campaign\CampaignService;
use App\Application\Campaign\CampaignValidationException;
use App\Domain\Campaign\Campaign;
use App\Http\Request;
use App\Http\Response\JsonResponse;

final class CampaignController
{
    public function __construct(private readonly CampaignService $campaigns)
    {
    }

    public function create(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(['campaign' => $this->serialize($this->campaigns->create($request->body()))], 201);
        } catch (CampaignValidationException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        }
    }

    public function list(): JsonResponse
    {
        return new JsonResponse(['campaigns' => array_map($this->serialize(...), $this->campaigns->list())]);
    }

    /** @return array<string, int|string> */
    private function serialize(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'budget_total' => $campaign->budgetTotal,
            'budget_used' => $campaign->budgetUsed,
            'starts_at' => $campaign->startsAt,
            'ends_at' => $campaign->endsAt,
            'status' => $campaign->status,
            'created_at' => $campaign->createdAt,
        ];
    }
}
