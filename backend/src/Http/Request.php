<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers = [],
        /** @var array<string, mixed> */
        private array $attributes = [],
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function attribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
}
