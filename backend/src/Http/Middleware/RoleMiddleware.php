<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\AuthenticatedPrincipal;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use Closure;

final class RoleMiddleware implements Middleware
{
    public function __construct(private readonly string $requiredRole)
    {
    }

    public function handle(Request $request, Closure $next): JsonResponse
    {
        $principal = $request->attribute('principal');

        if (!$principal instanceof AuthenticatedPrincipal) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        if ($principal->role !== $this->requiredRole) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
