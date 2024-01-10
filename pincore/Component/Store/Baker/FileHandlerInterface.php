<?php

namespace Pinoox\Component\Store\Baker;

interface FileHandlerInterface
{
    public function store(string $file, $data): void;

    public function retrieve(string $file);

    public function remove(string $file): void;
}