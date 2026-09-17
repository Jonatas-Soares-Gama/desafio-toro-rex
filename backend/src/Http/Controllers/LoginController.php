<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auth\AuthenticationException;
use App\Application\Auth\LoginService;
use App\Http\Response\JsonResponse;

final class LoginController
{
    public function __construct(private readonly LoginService $login)
    {
    }

    /** @param array<string, mixed> $body */
    public function __invoke(array $body): JsonResponse
    {
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (!is_string($email) || !is_string($password) || $email === '' || $password === '') {
            return new JsonResponse(['error' => 'Email and password are required.'], 422);
        }

        try {
            return new JsonResponse(['token' => $this->login->execute($email, $password)]);
        } catch (AuthenticationException) {
            return new JsonResponse(['error' => 'Invalid credentials.'], 401);
        }
    }
}
