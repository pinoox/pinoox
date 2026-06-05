<?php

namespace Pinoox\Component\Database\Patch;

use Pinoox\Portal\App\AppEngine;
use Pinoox\Support\SystemConfig;
use Pinoox\System\Model\MigrationModel;
use Symfony\Component\Finder\Finder;

class PatchToolkit
{
    private string $package = '';
    private string $patchPath = '';
    private array $errors = [];
    private array $patches = [];

    public function package(string $package): self
    {
        $this->package = $package;

        return $this;
    }

    public function load(): self
    {
        try {
            $this->initializePatchPath();
            $this->loadPatchFiles();
        } catch (\Exception $e) {
            $this->addError($e);
        }

        return $this;
    }

    public function getPatches(): array
    {
        return $this->patches;
    }

    public function getErrors(bool $latest = true): array|string
    {
        if ($latest) {
            return end($this->errors) ?: '';
        }

        return $this->errors;
    }

    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    public function recordName(string $patch): string
    {
        return 'patch:' . $patch;
    }

    public function hasRun(string $patch): bool
    {
        return MigrationModel::where('app', $this->package)
            ->where('migration', $this->recordName($patch))
            ->exists();
    }

    public function record(string $patch): void
    {
        MigrationModel::create([
            'migration' => $this->recordName($patch),
            'app' => $this->package,
            'batch' => $this->nextBatch(),
        ]);
    }

    private function initializePatchPath(): void
    {
        if ($this->package === 'pincore') {
            $this->patchPath = SystemConfig::path('system_patches');
            return;
        }

        $folder = trim(SystemConfig::rawPath('app_patches', 'database/patches'), '/\\');
        $this->patchPath = AppEngine::path($this->package) . '/' . $folder;
    }

    private function loadPatchFiles(): void
    {
        $this->ensurePatchDirectoryExists();

        $finder = new Finder();

        if (!is_dir($this->patchPath)) {
            return;
        }

        $finder->in($this->patchPath)->files()->name('*.php')->sortByName();

        foreach ($finder as $file) {
            $patchClass = require $file->getRealPath();

            if ($patchClass instanceof PatchBase) {
                $this->patches[] = [
                    'file' => $file->getRealPath(),
                    'name' => $file->getBasename('.php'),
                    'class' => get_class($patchClass),
                    'instance' => $patchClass,
                    'ran' => $this->hasRun($file->getBasename('.php')),
                    'created_at' => $this->createdAt($file->getBasename('.php'), $file->getRealPath()),
                ];
            }
        }
    }

    private function ensurePatchDirectoryExists(): void
    {
        if (!is_dir($this->patchPath)) {
            mkdir($this->patchPath, 0755, true);
        }
    }

    private function nextBatch(): int
    {
        $lastBatch = MigrationModel::where('app', $this->package)->max('batch');

        return ((int)($lastBatch ?? 0)) + 1;
    }

    private function createdAt(string $patch, string $file): ?string
    {
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})_/', $patch, $matches)) {
            return sprintf(
                '%s-%s-%s %s:%s:%s',
                $matches[1],
                $matches[2],
                $matches[3],
                $matches[4],
                $matches[5],
                $matches[6]
            );
        }

        return is_file($file) ? date('Y-m-d H:i:s', filemtime($file)) : null;
    }

    private function addError(\Exception|\Throwable|string $error): void
    {
        $this->errors[] = is_string($error) ? $error : $error->getMessage();
    }
}
