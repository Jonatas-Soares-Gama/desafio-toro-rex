<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Sales\SaleConflictException;
use App\Application\Sales\SaleConcurrencyException;
use App\Application\Sales\SaleNotFoundException;
use App\Application\Sales\SaleValidationException;
use App\Application\Sales\SalesService;
use App\Domain\Sale\Sale;
use App\Http\Request;
use App\Http\Response\JsonResponse;

final class SalesController
{
    public function __construct(private readonly SalesService $sales)
    {
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $result = $this->sales->create($request->body());
            $status = $result['created'] ? 201 : 200;
            $sale = $this->serialize($result['sale']);
            $sale['points'] = $result['points'];

            return new JsonResponse(['sale' => $sale], $status);
        } catch (SaleValidationException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        } catch (SaleConflictException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 409);
        } catch (SaleConcurrencyException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 503);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        try {
            $result = $this->sales->cancel($request->pathParameter('external_id') ?? '');

            return new JsonResponse([
                'sale' => $this->serialize($result['sale']),
                'reversed_points' => $result['reversedPoints'],
            ]);
        } catch (SaleNotFoundException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 404);
        } catch (SaleValidationException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        } catch (SaleConcurrencyException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 503);
        }
    }

    public function list(): JsonResponse
    {
        return new JsonResponse(['sales' => $this->sales->list()]);
    }

    /** @return array<string, int|string> */
    private function serialize(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'external_id' => $sale->externalId,
            'campaign_id' => $sale->campaignId,
            'seller_id' => $sale->sellerId,
            'product_id' => $sale->productId,
            'quantity' => $sale->quantity,
            'unit_value' => $sale->unitValue,
            'status' => $sale->status,
            'created_at' => $sale->createdAt,
        ];
    }
}
