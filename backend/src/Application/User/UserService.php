<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Infrastructure\Persistence\UserRepository;

final class UserService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** @param array<string, mixed> $input */
    /** @return array{id: int, name: string, email: string, role: string, created_at: string} */
    public function createSeller(array $input): array
    {
        [$name, $email, $password] = $this->validatedInput($input);

        return $this->users->createSeller($name, $email, password_hash($password, PASSWORD_DEFAULT));
    }

    /** @return list<array{id: int, name: string, email: string, role: string, created_at: string}> */
    public function listSellers(): array
    {
        return $this->users->allSellers();
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function validatedInput(array $input): array
    {
        $name = $input['name'] ?? null;
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!is_string($name) || !is_string($email) || !is_string($password)) {
            throw new UserValidationException('User fields are invalid.');
        }

        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || strlen($name) > 120 || strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new UserValidationException('User fields are invalid.');
        }

        if (strlen($password) < 8) {
            throw new UserValidationException('Password must have at least 8 characters.');
        }

        return [$name, $email, $password];
    }
}
