<?php

namespace App\com_pinoox_manager\Component;

use Pinoox\Portal\Config;

class WidgetHelper
{
    public static function registry(): array
    {
        return [
            'clock' => [
                'id' => 'clock',
                'title' => t('widget/clock.title'),
                'description' => t('widget/clock.description'),
                'configurable' => false,
            ],
            'storage' => [
                'id' => 'storage',
                'title' => t('widget/storage.title'),
                'description' => t('widget/storage.description'),
                'configurable' => true,
            ],
        ];
    }

    public static function defaults(): array
    {
        return [
            'clock' => ['visible' => true],
            'storage' => ['visible' => true],
        ];
    }

    public static function all(): array
    {
        $options = Config::name('options')->get() ?? [];
        $saved = is_array($options['widgets'] ?? null) ? $options['widgets'] : [];
        $defaults = self::defaults();
        $registry = self::registry();
        $result = [];

        foreach ($registry as $id => $meta) {
            $config = array_merge(
                $defaults[$id] ?? ['visible' => true],
                is_array($saved[$id] ?? null) ? $saved[$id] : []
            );

            $result[$id] = array_merge($meta, [
                'visible' => !empty($config['visible']),
            ]);
        }

        return $result;
    }

    public static function isVisible(string $id): bool
    {
        $widgets = self::all();

        return !empty($widgets[$id]['visible']);
    }

    public static function save(array $payload): array
    {
        $registry = self::registry();

        if ($payload === [])
            return ['saved' => false, 'message' => t('widget/storage.empty_payload')];

        $options = Config::name('options')->get() ?? [];
        $current = is_array($options['widgets'] ?? null) ? $options['widgets'] : self::defaults();

        foreach ($payload as $id => $config) {
            if (!isset($registry[$id]) || !is_array($config))
                continue;

            if (!isset($current[$id]) || !is_array($current[$id]))
                $current[$id] = self::defaults()[$id] ?? ['visible' => true];

            if (array_key_exists('visible', $config))
                $current[$id]['visible'] = (bool) $config['visible'];
        }

        Config::name('options')
            ->set('widgets', $current)
            ->save();

        return [
            'saved' => true,
            'widgets' => self::all(),
        ];
    }
}
