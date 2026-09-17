<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Infrastructure\Security\JwtTokenService;

final class LoginService
{
    public function __construct(
        private readonly UserFinder $users,
        private readonly JwtTokenService $tokens,
    ) {
    }

    public function execute(string $email, string $password): string
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return $this->tokens->issue((int) $user['id'], (string) $user['role']);
    }
}
