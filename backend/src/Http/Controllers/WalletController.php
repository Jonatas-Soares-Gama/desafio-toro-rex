<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Wallet\WalletService;
use App\Domain\User\AuthenticatedPrincipal;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use Throwable;

final class WalletController
{
    public function __construct(private readonly WalletService $wallet)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $principal = $request->attribute('principal');

        if (!$principal instanceof AuthenticatedPrincipal) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        try {
            return new JsonResponse(['wallet' => $this->wallet->show($principal->userId)]);
        } catch (Throwable) {
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
