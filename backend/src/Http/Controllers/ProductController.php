<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Product\DuplicateProductSkuException;
use App\Application\Product\ProductNotFoundException;
use App\Application\Product\ProductService;
use App\Application\Product\ProductValidationException;
use App\Http\Request;
use App\Http\Response\JsonResponse;

final class ProductController
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function create(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(['product' => $this->serialize($this->products->create($request->body()))], 201);
        } catch (ProductValidationException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        } catch (DuplicateProductSkuException) {
            return new JsonResponse(['error' => 'Product SKU already exists.'], 409);
        }
    }

    public function list(): JsonResponse
    {
        return new JsonResponse(['products' => array_map($this->serialize(...), $this->products->list())]);
    }

    public function update(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(['product' => $this->serialize($this->products->update($this->id($request), $request->body()))]);
        } catch (ProductValidationException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        } catch (DuplicateProductSkuException) {
            return new JsonResponse(['error' => 'Product SKU already exists.'], 409);
        } catch (ProductNotFoundException) {
            return new JsonResponse(['error' => 'Product not found.'], 404);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(['product' => $this->serialize($this->products->deactivate($this->id($request)))]);
        } catch (ProductNotFoundException) {
            return new JsonResponse(['error' => 'Product not found.'], 404);
        }
    }

    /** @return array<string, int|string|bool> */
    private function serialize(\App\Domain\Product\Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'points_per_unit' => $product->pointsPerUnit,
            'active' => $product->active,
            'created_at' => $product->createdAt,
        ];
    }

    private function id(Request $request): int
    {
        $id = $request->pathParameter('id');

        return is_numeric($id) && (int) $id > 0 ? (int) $id : 0;
    }
}
