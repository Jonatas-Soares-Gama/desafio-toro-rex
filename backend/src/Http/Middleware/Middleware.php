<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response\JsonResponse;
use Closure;

interface Middleware
{
    /** @param Closure(Request): JsonResponse $next */
    public function handle(Request $request, Closure $next): JsonResponse;
}
