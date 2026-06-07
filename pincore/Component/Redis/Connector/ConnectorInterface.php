<?php

namespace Pinoox\Component\Redis\Connector;

interface ConnectorInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function connect(array $config): object;
}

