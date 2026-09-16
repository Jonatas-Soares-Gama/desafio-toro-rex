<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Response\JsonResponse;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
