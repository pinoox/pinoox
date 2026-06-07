<?php

use Pinoox\Component\Store\Config\ConfigInterface;
use Pinoox\Component\Store\FileSystem\FilesystemManager;
use Pinoox\Support\SystemConfig;

beforeEach(function () {
    SystemConfig::clearCache();
    deleteStorageFilesystemTestDirectory(str_replace('\\', '/', dirname(__DIR__) . '/Fixtures/storage_apps'));
});

afterEach(function () {
    SystemConfig::clearCache();
    deleteStorageFilesystemTestDirectory(str_replace('\\', '/', dirname(__DIR__) . '/Fixtures/storage_apps'));
});

it('creates app scoped filesystems for pinoox packages', function () {
    $root = str_replace('\\', '/', dirname(__DIR__) . '/Fixtures/storage_apps');

    $manager = new FilesystemManager(new ArrayConfig([
        'default' => 'local',
        'app_disk' => 'local',
        'app_root' => 'tests/Fixtures/storage_apps',
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => '~storage/app',
                'throw' => true,
            ],
        ],
    ]));

    expect($manager->appPath('com_pinoox_manager'))->toBe($root . '/com_pinoox_manager');

    $disk = $manager->app('com_pinoox_manager');
    $disk->put('notes/readme.txt', 'hello pinoox');

    expect(is_file($root . '/com_pinoox_manager/notes/readme.txt'))->toBeTrue()
        ->and(file_get_contents($root . '/com_pinoox_manager/notes/readme.txt'))->toBe('hello pinoox');

    deleteStorageFilesystemTestDirectory($root);
});

class ArrayConfig implements ConfigInterface
{
    public function __construct(private array $items)
    {
    }

    public function get(?string $key = null, $default = null): mixed
    {
        if ($key === null || $key === '') {
            return $this->items;
        }

        $data = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }

            $data = $data[$segment];
        }

        return $data;
    }

    public function set(string $key, mixed $value): static
    {
        return $this;
    }

    public function remove(string $key): static
    {
        return $this;
    }

    public function save(): static
    {
        return $this;
    }
}

function deleteStorageFilesystemTestDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}

