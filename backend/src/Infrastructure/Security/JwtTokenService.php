<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtTokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttlInSeconds,
    ) {
        if (strlen($this->secret) < 32) {
            throw new \InvalidArgumentException('JWT secret must contain at least 32 characters.');
        }
    }

    public function issue(int $userId, string $role): string
    {
        $issuedAt = time();

        return JWT::encode([
            'sub' => $userId,
            'role' => $role,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->ttlInSeconds,
        ], $this->secret, 'HS256');
    }

    /** @return array{sub: int, role: string} */
    public function decode(string $token): array
    {
        $claims = (array) JWT::decode($token, new Key($this->secret, 'HS256'));

        return [
            'sub' => (int) ($claims['sub'] ?? 0),
            'role' => (string) ($claims['role'] ?? ''),
        ];
    }
}
