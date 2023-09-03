<?php

namespace pinoox\component\store\baker;

interface FileHandlerInterface
{
    public function store(string $file, $data): void;

    public function retrieve(string $file);

    public function remove(string $file): void;
}