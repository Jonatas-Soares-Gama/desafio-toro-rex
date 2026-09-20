<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\User\DuplicateUserEmailException;
use App\Application\User\UserService;
use App\Application\User\UserValidationException;
use App\Http\Request;
use App\Http\Response\JsonResponse;

final class UserController
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function createSeller(Request $request): JsonResponse
    {
        try {
            return new JsonResponse(['user' => $this->users->createSeller($request->body())], 201);
        } catch (UserValidationException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        } catch (DuplicateUserEmailException) {
            return new JsonResponse(['error' => 'User email already exists.'], 409);
        }
    }

    public function listSellers(): JsonResponse
    {
        return new JsonResponse(['sellers' => $this->users->listSellers()]);
    }
}
