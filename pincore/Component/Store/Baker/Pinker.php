<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component\Store\Baker;

use Closure;
use Pinoox\Component\Helpers\HelperAnnotations;
use Pinoox\Component\Helpers\HelperObject;
use Pinoox\Component\Helpers\Str;

/**
 * Class to manage Pinker data
 */
class Pinker
{

    private const CACHE_SCHEMA = 2;

    private const OVERRIDE_SCHEMA = 1;

    private $data = null;

    private ?array $info = null;
    /** @var array<string, mixed> */
    private array $forcedOverridePaths = [];
    /** @var array<string, mixed> */
    private array $runtimeDefaults = [];
    private bool $isOutputData = false;
    private bool $dumping = false;
    private bool $isCamelToUnderscore = false;
    private string $bakedFile = '';
    private string $mainFile = '';
    private FileHandlerInterface $fileHandler;
    /**
     * @var Pinker[]
     */
    private array $objs = [];

    public function __construct(?string $mainFile = '', ?string $bakedFile = '', ?FileHandlerInterface $fileHandler = null)
    {
        $this->mainFile = $mainFile;
        $this->bakedFile = $bakedFile;
        $this->fileHandler = $fileHandler ?? new FileHandler();
    }

    public static function create(?string $mainFile = '', ?string $bakedFile = ''): static
    {
        return new static($mainFile, $bakedFile);
    }

    public function data($data): self
    {
        $this->data = $data;
        $this->isOutputData = true;
        return $this;
    }

    public function dumping(bool $status = true): self
    {
        $this->dumping = $status;
        return $this;
    }

    public function camelToUnderscore(bool $status = true): self
    {
        $this->isCamelToUnderscore = $status;
        return $this;
    }

    public function info(array $info): self
    {
        $this->info = array_merge($this->info ?? [], $info);

        return $this;
    }

    /**
     * Always persist these dot-paths in the override file (installer defaults, etc.).
     *
     * @param array<string, mixed> $paths
     */
    public function forceOverridePaths(array $paths): self
    {
        foreach ($paths as $path => $value) {
            $this->forcedOverridePaths[(string) $path] = $value;
        }

        return $this;
    }

    /**
     * Runtime-only defaults merged on read but not persisted to pinker on bake.
     *
     * @param array<string, mixed> $defaults
     */
    public function runtimeDefaults(array $defaults): self
    {
        $this->runtimeDefaults = $defaults;

        return $this;
    }

    public function isEnvSensitive(): bool
    {
        return EnvSensitiveConfig::sourceUsesEnv($this->mainFile);
    }

    public function bake(): self
    {
        if (!empty($this->bakedFile)) {
            if ($this->usesOverlayStorage()) {
                $this->bakeOverride();
                return $this;
            }

            if (!$this->dumping) {
                $config = $this->format($this->generateData());
            } else {
                $config = $this->transmutation();
            }
            $this->fileHandler->store($this->bakedFile, $config);
        }

        return $this;
    }

    public function rebuild(): self
    {
        if (!$this->usesOverlayStorage()) {
            $this->restore();
            return $this;
        }

        $this->removeCache();
        $this->data = $this->sourceData();
        $this->bakeCache($this->data);

        return $this;
    }

    private function format($data): string
    {
        $tags = $this->generateInformation();
        $docBlock = HelperAnnotations::generateDocBlock('Pinoox Baker', $tags);

        return '<?' . 'php' . "\n" .
            $docBlock . "\n\n" .
            'return ' . var_export($data, true) . ';';
    }

    /**
     * Generate info for bake
     *
     * @return array
     */
    private function generateInformation(): array
    {
        $data = $this->data;
        $mainInfo = $this->info;
        $mainInfo = !is_array($mainInfo) ? [] : $mainInfo;
        $info = [];

        if (is_array($data) && isset($data['__pinker__']) && $data['__pinker__']) {
            $info = !is_array($data['info']) ? [] : $data['info'];;
        }

        return array_merge(
            [
                'time' => time(),
            ],
            $info,
            $this->sourceInfo(),
            $mainInfo,
        );
    }

    private function transmutation(): array|string
    {
        $tags = $this->generateInformation();
        $docBlock = HelperAnnotations::generateDocBlock('Pinoox Baker', $tags);

        return '<?' . 'php' . "\n" .
            $docBlock . "\n\n" .
            'return ' . $this->exportPhp($this->generateData()) . ';';
    }

    private function exportPhp(mixed $data, int $level = 0): string
    {
        if ($data instanceof Closure) {
            return HelperObject::closure_dump($data);
        }

        if (!is_array($data)) {
            return var_export($data, true);
        }

        if ($data === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $level);
        $childIndent = str_repeat('    ', $level + 1);
        $items = [];

        foreach ($data as $key => $value) {
            $key = is_string($key) && $level === 0 && $this->isCamelToUnderscore
                ? Str::camelToUnderscore($key)
                : $key;
            $items[] = $childIndent . var_export($key, true) . ' => ' . $this->exportPhp($value, $level + 1);
        }

        return "[\n" . implode(",\n", $items) . "\n" . $indent . "]";
    }

    public function pickup()
    {
        $data = $this->getData();
        $info = $this->getInfo();
        $lifetime = @$info['lifetime'];

        if (!empty($lifetime) && is_numeric($lifetime)) {
            $lifetime = @$info['time'] + $lifetime;

            if ($lifetime < time()) {
                $this->remove();
                $data = $this->getData();
            }
        }

        return @$data;
    }

    public function remove(): void
    {
        $this->removeCache();
        $this->removeOverride();
    }

    public function removeCache(): void
    {
        if(is_file($this->bakedFile))
            $this->fileHandler->remove($this->bakedFile);
    }

    public function removeOverride(): void
    {
        $overrideFile = $this->getOverrideFile();

        if ($overrideFile !== null && is_file($overrideFile)) {
            $this->fileHandler->remove($overrideFile);
        }
    }

    public function getInfo(?string $key = null): array|string|null
    {
        $info = HelperAnnotations::getTagsCurrentBlockInFile($this->bakedFile);
        return !is_null($key) ? @$info[$key] : $info;
    }

    public function restore(): void
    {
        $this->fileHandler->remove($this->bakedFile);
        $this->removeOverride();
        $this->forcedOverridePaths = [];
        $this->data = is_file($this->mainFile) ? $this->fileHandler->retrieve($this->mainFile) : null;
        $this->usesOverlayStorage() ? $this->bakeCache($this->data) : $this->bake();
    }

    private function getData()
    {
        if ($this->usesOverlayStorage()) {
            if ($this->isSourceEnvSensitive()) {
                return $this->resolveEnvSensitiveData();
            }

            $source = $this->sourceDataForRead();

            return $this->applyOverride($source);
        }

        if (!is_file($this->bakedFile) && is_file($this->mainFile)) {
            $this->data = is_file($this->mainFile) ? $this->fileHandler->retrieve($this->mainFile) : null;
            $this->bake();
        }

        return $this->isOutputData ? $this->data : (is_file($this->bakedFile) ? $this->fileHandler->retrieve($this->bakedFile) : null);
    }

    private function generateData()
    {
        $data = $this->data;
        if (is_array($data) && isset($data['__pinker__']) && $data['__pinker__'] === true) {
            return @$data['data'];
        }

        return $data;
    }

    public function build($data, array $info = []): array
    {
        if (is_callable($data)) {
            $data = $data();
        }

        return [
            'data' => $data,
            'info' => $info,
            '__pinker__' => true,
        ];
    }

    /**
     * @return string
     */
    public function getBakedFile(): string
    {
        return $this->bakedFile;
    }

    /**
     * @return string
     */
    public function getMainFile(): string
    {
        return $this->mainFile;
    }

    public function getOverrideFile(): ?string
    {
        if (!$this->usesOverlayStorage()) {
            return null;
        }

        $bakedFile = $this->normalizePath($this->bakedFile);
        $root = $this->pinkerRoot();

        if ($root === null || !str_starts_with($bakedFile, $root . '/')) {
            return null;
        }

        return $root . '/state/' . substr($bakedFile, strlen($root) + 1);
    }

    public function status(): array
    {
        $info = $this->sourceInfo();
        $override = $this->loadOverride();

        return [
            'source' => $this->mainFile,
            'cache' => $this->bakedFile,
            'override' => $this->getOverrideFile(),
            'source_exists' => is_file($this->mainFile),
            'cache_exists' => is_file($this->bakedFile),
            'override_exists' => $override !== null,
            'cache_valid' => $this->isCacheValid(),
            'env_sensitive' => $this->isSourceEnvSensitive(),
            'env_priority' => $this->envPriorityStatus($override),
            'env_resolution' => $this->envResolutionStatus($override),
            'runtime_mode' => EnvSensitiveConfig::currentMode(),
            'stored_profiles' => $this->storedProfilesStatus($override),
            'source_hash' => $info['source_hash'] ?? null,
            'source_mtime' => $info['source_mtime'] ?? null,
            'source_size' => $info['source_size'] ?? null,
            'override_sets' => is_array($override['data'] ?? null) ? count($override['data']) : 0,
            'override_removes' => is_array($override['remove'] ?? null) ? count($override['remove']) : 0,
            'override_updated_at' => $override['info']['updated_at'] ?? null,
        ];
    }

    private function envResolutionStatus(?array $override): ?string
    {
        if (!$this->isSourceEnvSensitive()) {
            return null;
        }

        return (string) ($override['info']['env_resolution']
            ?? $this->info['env_resolution']
            ?? EnvSensitiveConfig::resolutionLabel());
    }

    private function envPriorityStatus(?array $override): ?string
    {
        if (!$this->isSourceEnvSensitive()) {
            return null;
        }

        return (string) ($override['info']['env_priority']
            ?? $this->info['env_priority']
            ?? EnvSensitiveConfig::envPriorityLabel());
    }

    private function storedProfilesStatus(?array $override): ?string
    {
        if (!$this->isSourceEnvSensitive()) {
            return null;
        }

        return (string) ($override['info']['stored_profiles']
            ?? $this->info['stored_profiles']
            ?? implode(',', EnvSensitiveConfig::storedProfiles()));
    }

    private function usesOverlayStorage(): bool
    {
        if ($this->mainFile === '' || $this->bakedFile === '') {
            return false;
        }

        if ($this->normalizePath($this->mainFile) === $this->normalizePath($this->bakedFile)) {
            return false;
        }

        $root = $this->pinkerRoot();

        return $root !== null && str_starts_with($this->normalizePath($this->bakedFile), $root . '/');
    }

    private function sourceDataForRead()
    {
        if (!$this->isCacheValid()) {
            $this->removeCache();
            $this->data = $this->sourceData();
            $this->bakeCache($this->data);
        }

        if (!is_file($this->bakedFile)) {
            return $this->sourceData();
        }

        $data = $this->fileHandler->retrieve($this->bakedFile);

        if ($data === null && is_file($this->mainFile)) {
            $this->removeCache();
            $this->data = $this->sourceData();
            $this->bakeCache($this->data);

            return is_file($this->bakedFile) ? $this->fileHandler->retrieve($this->bakedFile) : $this->data;
        }

        return $data;
    }

    private function resolveEnvSensitiveData()
    {
        $source = $this->sourceData();
        $override = $this->loadOverride();

        if (EnvSensitiveConfig::shouldResolveFromEnv()) {
            if ($override !== null && !empty($override['data'])) {
                return $this->applyOverride($source);
            }

            return $source;
        }

        if ($override === null || empty($override['data'])) {
            return $source;
        }

        return $this->applyOverride($source);
    }

    private function sourceData()
    {
        return is_file($this->mainFile) ? $this->fileHandler->retrieve($this->mainFile) : null;
    }

    private function bakeCache($data): void
    {
        if ($this->isSourceEnvSensitive()) {
            return;
        }

        $this->data = $data;
        $this->fileHandler->store($this->bakedFile, $this->dumping ? $this->transmutation() : $this->format($this->generateData()));
    }

    private function bakeOverride(): void
    {
        $overrideFile = $this->getOverrideFile();

        if ($overrideFile === null) {
            return;
        }

        $source = $this->sourceData();
        $current = $this->generateData();

        if ($this->runtimeDefaults !== [] && is_array($current)) {
            $current = $this->withoutRuntimeDefaults(
                is_array($source) ? $source : [],
                $current,
                $this->runtimeDefaults,
            );
        }

        $override = $this->makeOverride($source, $current);

        if ($this->forcedOverridePaths !== []) {
            $override['data'] = array_replace($override['data'], $this->forcedOverridePaths);
        }

        if (empty($override['data']) && empty($override['remove'])) {
            $this->removeOverride();
            return;
        }

        $this->fileHandler->store($overrideFile, $this->formatExported([
            '__pinker_override__' => true,
            'schema' => self::OVERRIDE_SCHEMA,
            'data' => $override['data'],
            'remove' => $override['remove'],
            'info' => array_merge([
                'source' => $this->mainFile,
                'cache' => $this->bakedFile,
                'updated_at' => time(),
            ], $this->overrideInfo()),
        ]));
    }

    /** @return array<string, scalar|null> */
    private function overrideInfo(): array
    {
        if (!$this->isSourceEnvSensitive()) {
            return [];
        }

        return array_merge([
            'env_sensitive' => 'yes',
            'env_priority' => EnvSensitiveConfig::envPriorityLabel(),
            'env_resolution' => EnvSensitiveConfig::resolutionLabel(),
            'stored_profiles' => implode(',', EnvSensitiveConfig::storedProfiles()),
        ], $this->info ?? []);
    }

    private function formatExported(mixed $data): string
    {
        $tags = $this->generateInformation();
        $docBlock = HelperAnnotations::generateDocBlock('Pinoox Baker', $tags);

        return '<?' . 'php' . "\n" .
            $docBlock . "\n\n" .
            'return ' . $this->exportPhp($data) . ';';
    }

    private function applyOverride($source)
    {
        $override = $this->loadOverride();

        if ($override === null) {
            return $source;
        }

        $sourceArray = is_array($source) ? $source : [];
        $data = $sourceArray;
        $sourceIsNewer = $this->isSourceNewerThanOverride($override);
        $pruneData = [];
        $pruneRemove = [];

        foreach (($override['data'] ?? []) as $path => $value) {
            $path = (string) $path;

            if (EnvSensitiveConfig::shouldSkipPinkerPath($this->mainFile, $path)) {
                continue;
            }

            if ($sourceIsNewer && $this->pathExists($sourceArray, $path)) {
                $pruneData[] = $path;
                continue;
            }

            $this->setPath($data, $path, $value);
        }

        foreach (($override['remove'] ?? []) as $path) {
            $path = (string) $path;

            if (EnvSensitiveConfig::shouldSkipPinkerPath($this->mainFile, $path)) {
                continue;
            }

            if ($sourceIsNewer && $this->pathExists($sourceArray, $path)) {
                $pruneRemove[] = $path;
                continue;
            }

            $this->removePath($data, $path);
        }

        if ($sourceIsNewer && ($pruneData !== [] || $pruneRemove !== [])) {
            $this->pruneOverridePaths($pruneData, $pruneRemove);
        }

        return $data;
    }

    private function isSourceNewerThanOverride(array $override): bool
    {
        if (!is_file($this->mainFile)) {
            return false;
        }

        $overrideUpdatedAt = (int) ($override['info']['updated_at'] ?? 0);

        if ($overrideUpdatedAt === 0) {
            return false;
        }

        return filemtime($this->mainFile) > $overrideUpdatedAt;
    }

    private function pathExists(array $data, string $path): bool
    {
        $keys = explode('.', $path);
        $target = $data;

        foreach ($keys as $key) {
            if (!is_array($target) || !array_key_exists($key, $target)) {
                return false;
            }

            $target = $target[$key];
        }

        return true;
    }

    private function pruneOverridePaths(array $dataPaths, array $removePaths): void
    {
        $override = $this->loadOverride();

        if ($override === null) {
            return;
        }

        foreach ($dataPaths as $path) {
            unset($override['data'][$path]);
        }

        if ($removePaths !== []) {
            $override['remove'] = array_values(array_diff($override['remove'] ?? [], $removePaths));
        }

        if (empty($override['data']) && empty($override['remove'])) {
            $this->removeOverride();
            return;
        }

        $overrideFile = $this->getOverrideFile();

        if ($overrideFile === null) {
            return;
        }

        $this->fileHandler->store($overrideFile, $this->formatExported([
            '__pinker_override__' => true,
            'schema' => self::OVERRIDE_SCHEMA,
            'data' => $override['data'] ?? [],
            'remove' => $override['remove'] ?? [],
            'info' => $override['info'] ?? [],
        ]));
    }

    private function loadOverride(): ?array
    {
        $overrideFile = $this->getOverrideFile();

        if ($overrideFile === null || !is_file($overrideFile)) {
            return null;
        }

        $data = $this->fileHandler->retrieve($overrideFile);

        return is_array($data) && ($data['__pinker_override__'] ?? false) === true ? $data : null;
    }

    private function makeOverride($source, $current): array
    {
        $source = is_array($source) ? $source : [];
        $current = is_array($current) ? $current : [];

        return [
            'data' => $this->diffData($source, $current),
            'remove' => $this->removedPaths($source, $current),
        ];
    }

    private function diffData(array $source, array $current, string $prefix = ''): array
    {
        $diff = [];

        foreach ($current as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;

            if (!array_key_exists($key, $source)) {
                $diff[$path] = $value;
                continue;
            }

            if (is_array($source[$key]) && is_array($value) && $this->isAssoc($source[$key]) && $this->isAssoc($value)) {
                $diff += $this->diffData($source[$key], $value, $path);
                continue;
            }

            if ($source[$key] !== $value) {
                $diff[$path] = $value;
            }
        }

        return $diff;
    }

    private function removedPaths(array $source, array $current, string $prefix = ''): array
    {
        $removed = [];

        foreach ($source as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;

            if (!array_key_exists($key, $current)) {
                $removed[] = $path;
                continue;
            }

            if (is_array($value) && is_array($current[$key]) && $this->isAssoc($value) && $this->isAssoc($current[$key])) {
                $removed = array_merge($removed, $this->removedPaths($value, $current[$key], $path));
            }
        }

        return $removed;
    }

    private function setPath(array &$data, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $target = &$data;

        foreach ($keys as $key) {
            if (!isset($target[$key]) || !is_array($target[$key])) {
                $target[$key] = [];
            }

            $target = &$target[$key];
        }

        $target = $value;
    }

    private function removePath(array &$data, string $path): void
    {
        $keys = explode('.', $path);
        $last = array_pop($keys);
        $target = &$data;

        foreach ($keys as $key) {
            if (!isset($target[$key]) || !is_array($target[$key])) {
                return;
            }

            $target = &$target[$key];
        }

        unset($target[$last]);
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * @param array<string, mixed> $source
     * @param array<string, mixed> $current
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    private function withoutRuntimeDefaults(array $source, array $current, array $defaults): array
    {
        $result = $current;

        foreach ($defaults as $key => $defaultValue) {
            if (!array_key_exists($key, $result)) {
                continue;
            }

            if (!array_key_exists($key, $source)) {
                if ($this->valuesEqual($result[$key], $defaultValue)) {
                    unset($result[$key]);
                }

                continue;
            }

            if (
                is_array($defaultValue)
                && is_array($source[$key])
                && is_array($result[$key])
                && $this->isAssoc($defaultValue)
                && $this->isAssoc($source[$key])
                && $this->isAssoc($result[$key])
            ) {
                $result[$key] = $this->withoutRuntimeDefaults($source[$key], $result[$key], $defaultValue);
            }
        }

        return $result;
    }

    private function valuesEqual(mixed $left, mixed $right): bool
    {
        if ($left === $right) {
            return true;
        }

        if (!is_array($left) || !is_array($right)) {
            return false;
        }

        return $this->normalizeArray($left) == $this->normalizeArray($right);
    }

    private function normalizeArray(array $array): array
    {
        if (!$this->isAssoc($array)) {
            $normalized = [];

            foreach ($array as $item) {
                $normalized[] = is_array($item) ? $this->normalizeArray($item) : $item;
            }

            return $normalized;
        }

        ksort($array);
        $normalized = [];

        foreach ($array as $key => $value) {
            $normalized[$key] = is_array($value) ? $this->normalizeArray($value) : $value;
        }

        return $normalized;
    }

    private function isCacheValid(): bool
    {
        if (!is_file($this->bakedFile)) {
            return false;
        }

        $info = $this->getInfo();

        if ((int)($info['schema'] ?? 0) !== self::CACHE_SCHEMA || !is_file($this->mainFile)) {
            return false;
        }

        $mtime = filemtime($this->mainFile);
        $size = filesize($this->mainFile);

        if ((int)($info['source_mtime'] ?? 0) !== (int)$mtime) {
            return false;
        }

        if (isset($info['source_size'])) {
            return (int)$info['source_size'] === (int)$size;
        }

        return ($info['source_hash'] ?? null) === sha1_file($this->mainFile);
    }

    private function sourceInfo(): array
    {
        return [
            'schema' => self::CACHE_SCHEMA,
            'source' => $this->mainFile,
            'source_hash' => is_file($this->mainFile) ? sha1_file($this->mainFile) : null,
            'source_mtime' => is_file($this->mainFile) ? filemtime($this->mainFile) : null,
            'source_size' => is_file($this->mainFile) ? filesize($this->mainFile) : null,
            'env_sensitive' => $this->isSourceEnvSensitive() ? 'yes' : 'no',
        ];
    }

    private function isSourceEnvSensitive(): bool
    {
        return EnvSensitiveConfig::sourceUsesEnv($this->mainFile);
    }

    private function pinkerRoot(): ?string
    {
        $bakedFile = $this->normalizePath($this->bakedFile);
        $needle = '/pinker/';
        $pos = strpos($bakedFile, $needle);

        if ($pos === false) {
            return null;
        }

        return substr($bakedFile, 0, $pos + strlen('/pinker'));
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}

