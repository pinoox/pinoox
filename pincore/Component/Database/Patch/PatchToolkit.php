<?php

namespace Pinoox\Component\Database\Patch;

use Pinoox\Component\Migration\MigrationQuery;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Database\DB;
use Pinoox\Support\SystemConfig;
use Pinoox\Model\HistoryModel;
use Pinoox\Model\Table;
use Symfony\Component\Finder\Finder;

class PatchToolkit
{

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_ROLLED_BACK = 'rolled_back';

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
        } catch (\Throwable $e) {
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
        return $patch;
    }

    public function hasRun(string $patch): bool
    {
        if (!$this->historyTableExists()) {
            return false;
        }

        return HistoryModel::where('type', MigrationQuery::TYPE_PATCH)
            ->where('app', $this->package)
            ->whereIn('migration', [$this->recordName($patch), 'patch:' . $patch])
            ->where('status', self::STATUS_SUCCESS)
            ->exists();
    }

    public function record(string $patch): void
    {
        $this->recordSuccess($patch);
    }

    public function recordSuccess(string $patch, ?string $checksum = null, ?int $durationMs = null, array $metadata = []): void
    {
        $this->recordHistory($patch, self::STATUS_SUCCESS, $checksum, $durationMs, null, $metadata);
    }

    public function recordSkipped(string $patch, ?string $checksum = null, ?int $durationMs = null, array $metadata = []): void
    {
        $this->recordHistory($patch, self::STATUS_SKIPPED, $checksum, $durationMs, null, $metadata);
    }

    public function recordFailed(string $patch, \Throwable $error, ?string $checksum = null, ?int $durationMs = null, array $metadata = []): void
    {
        $this->recordHistory($patch, self::STATUS_FAILED, $checksum, $durationMs, $error->getMessage(), $metadata);
    }

    public function recordRolledBack(string $patch, ?string $checksum = null, ?int $durationMs = null, array $metadata = []): void
    {
        $this->recordHistory($patch, self::STATUS_ROLLED_BACK, $checksum, $durationMs, null, $metadata);
    }

    public function deleteSuccessRecord(string $patch): void
    {
        HistoryModel::where('type', MigrationQuery::TYPE_PATCH)
            ->where('app', $this->package)
            ->where('migration', $this->recordName($patch))
            ->where('status', self::STATUS_SUCCESS)
            ->delete();
    }

    public function latestRecord(string $patch): ?array
    {
        if (!$this->historyTableExists()) {
            return null;
        }

        $record = HistoryModel::where('type', MigrationQuery::TYPE_PATCH)
            ->where('app', $this->package)
            ->whereIn('migration', [$this->recordName($patch), 'patch:' . $patch])
            ->orderBy('id', 'DESC')
            ->first();

        return $record?->toArray();
    }

    private function recordHistory(
        string $patch,
        string $status,
        ?string $checksum = null,
        ?int $durationMs = null,
        ?string $error = null,
        array $metadata = []
    ): void
    {
        HistoryModel::create([
            'type' => MigrationQuery::TYPE_PATCH,
            'migration' => $this->recordName($patch),
            'app' => $this->package,
            'batch' => $this->nextBatch(),
            'status' => $status,
            'checksum' => $checksum,
            'duration_ms' => $durationMs,
            'executed_at' => date('Y-m-d H:i:s'),
            'error' => $error,
            'metadata' => empty($metadata) ? null : json_encode($metadata),
        ]);
    }

    private function initializePatchPath(): void
    {
        if ($this->package === 'platform') {
            $this->patchPath = SystemConfig::platformPath('patches');
            return;
        }

        $folder = trim(SystemConfig::rawPath('app_patches', 'patches'), '/\\');
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
                $patchClass->setPackage($this->package);
                $name = $file->getBasename('.php');
                $record = $this->latestRecord($name);

                $this->patches[] = [
                    'file' => $file->getRealPath(),
                    'name' => $name,
                    'class' => get_class($patchClass),
                    'instance' => $patchClass,
                    'ran' => $this->hasRun($name),
                    'should_run' => $patchClass->shouldRun(),
                    'can_rollback' => $patchClass->canRollback(),
                    'description' => $patchClass->description(),
                    'checksum' => $this->checksum($file->getRealPath()),
                    'record' => $record,
                    'status' => $record['status'] ?? 'pending',
                    'created_at' => $this->createdAt($name, $file->getRealPath()),
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
        if (!$this->historyTableExists()) {
            return 1;
        }

        $lastBatch = HistoryModel::where('type', MigrationQuery::TYPE_PATCH)
            ->where('app', $this->package)
            ->max('batch');

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

    private function checksum(string $file): ?string
    {
        return is_file($file) ? hash_file('sha256', $file) : null;
    }

    private function historyTableExists(): bool
    {
        try {
            return DB::schema('platform')->hasTable(DB::tableName(Table::HISTORY, 'platform'));
        } catch (\Throwable) {
            return false;
        }
    }
}

