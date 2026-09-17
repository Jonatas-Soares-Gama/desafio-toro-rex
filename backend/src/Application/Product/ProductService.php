<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Product\Product;
use App\Infrastructure\Persistence\ProductRepository;

final class ProductService
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): Product
    {
        [$name, $sku, $points] = $this->validatedInput($input);

        return $this->products->create($name, $sku, $points);
    }

    /** @return list<Product> */
    public function list(): array
    {
        return $this->products->all();
    }

    /** @param array<string, mixed> $input */
    public function update(int $id, array $input): Product
    {
        [$name, $sku, $points] = $this->validatedInput($input);

        return $this->products->update($id, $name, $sku, $points);
    }

    public function deactivate(int $id): Product
    {
        return $this->products->deactivate($id);
    }

    /** @return array{0: string, 1: string, 2: int} */
    private function validatedInput(array $input): array
    {
        $name = $input['name'] ?? null;
        $sku = $input['sku'] ?? null;
        $points = $input['points_per_unit'] ?? null;

        if (!is_string($name) || !is_string($sku) || !is_int($points)) {
            throw new ProductValidationException('Product fields are invalid.');
        }

        $name = trim($name);
        $sku = trim($sku);

        try {
            Product::validateAttributes($name, $sku, $points);
        } catch (\InvalidArgumentException $exception) {
            throw new ProductValidationException($exception->getMessage(), 0, $exception);
        }

        return [$name, $sku, $points];
    }
}
