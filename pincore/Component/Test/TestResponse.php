<?php

namespace Pinoox\Component\Test;

use Pinoox\Component\Http\Response;

class TestResponse
{
    public function __construct(
        private readonly Response $response,
    ) {
    }

    public function response(): Response
    {
        return $this->response;
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function content(): string
    {
        return (string) $this->response->getContent();
    }

    public function json(?string $key = null): mixed
    {
        $data = json_decode($this->content(), true);

        if ($key === null) {
            return $data;
        }

        return data_get($data, $key);
    }

    public function assertOk(): self
    {
        expect($this->status())->toBe(200);

        return $this;
    }

    public function assertStatus(int $status): self
    {
        expect($this->status())->toBe($status);

        return $this;
    }

    public function assertJsonPath(string $key, mixed $value): self
    {
        expect($this->json($key))->toBe($value);

        return $this;
    }

    public function assertSee(string $needle): self
    {
        expect($this->content())->toContain($needle);

        return $this;
    }
}

