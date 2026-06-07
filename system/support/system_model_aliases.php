<?php

$aliases = [
    'Pinoox\\Model\\Table' => 'Pinoox\\System\\Model\\Table',
    'Pinoox\\Model\\UserModel' => 'Pinoox\\System\\Model\\UserModel',
    'Pinoox\\Model\\FileModel' => 'Pinoox\\System\\Model\\FileModel',
    'Pinoox\\Model\\TokenModel' => 'Pinoox\\System\\Model\\TokenModel',
    'Pinoox\\Model\\HistoryModel' => 'Pinoox\\System\\Model\\HistoryModel',
    'Pinoox\\Model\\RoleModel' => 'Pinoox\\System\\Model\\RoleModel',
    'Pinoox\\Model\\PermissionModel' => 'Pinoox\\System\\Model\\PermissionModel',
    'Pinoox\\Model\\Scope\\AppScope' => 'Pinoox\\System\\Model\\Scope\\AppScope',
];

foreach ($aliases as $alias => $class) {
    if (!class_exists($alias, false) && class_exists($class)) {
        class_alias($class, $alias);
    }
}
