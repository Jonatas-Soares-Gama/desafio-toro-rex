<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\AuthenticatedPrincipal;
use App\Http\Request;
use App\Http\Response\JsonResponse;
use App\Infrastructure\Security\JwtTokenService;
use Closure;
use Throwable;

final class AuthenticationMiddleware implements Middleware
{
    public function __construct(private readonly JwtTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): JsonResponse
    {
        $authorization = $request->header('Authorization');

        if ($authorization === null || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        try {
            $claims = $this->tokens->decode(trim($matches[1]));
            $request->setAttribute(
                'principal',
                new AuthenticatedPrincipal($claims['sub'], $claims['role']),
            );
        } catch (Throwable) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

        return $next($request);
    }
}
