<?php

namespace Pinoox\Component\Access;

use Pinoox\Component\Transport\TransportConfig;
use Pinoox\Component\Transport\TransportScenario;
use Pinoox\Portal\App\App;

class AccessConfig
{
    /**
     * @return array{
     *     enabled: bool,
     *     package: string,
     *     super_roles: list<string>,
     *     groups: array<string, list<string>>
     * }
     */
    public static function resolve(): array
    {
        $config = App::get('access') ?? [];

        if (!is_array($config)) {
            $config = [];
        }

        return [
            'enabled' => (bool) ($config['enabled'] ?? true),
            'package' => TransportConfig::package(TransportScenario::ACCESS_TABLE),
            'super_roles' => array_values($config['super_roles'] ?? ['admin', 'superadmin']),
            'groups' => is_array($config['groups'] ?? null) ? $config['groups'] : [],
        ];
    }
}

