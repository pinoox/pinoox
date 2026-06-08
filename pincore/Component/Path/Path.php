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

namespace Pinoox\Component\Path;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Package\Engine\EngineInterface;
use Pinoox\Component\Package\Parser\ParserInterface;
use Pinoox\Component\Package\Reference\NameReference;
use Pinoox\Component\Package\Reference\ReferenceInterface;
use Pinoox\Component\Path\Manager\PathManager;
use Pinoox\Support\SystemApp;
use Pinoox\Support\SystemConfig;

class Path implements PathInterface
{
    /**
     * @var array<string, string>
     */
    private array $paths = [];

    public function __construct(
        private readonly string          $basePath,
        private readonly ParserInterface $parser,
        private readonly EngineInterface $appEngine,
        private ?string                   $package,
    )
    {
    }

    /**
     * Project root directory.
     */
    public function root(): string
    {
        return $this->get('~');
    }

    /**
     * Apps directory or a specific app folder.
     */
    public function apps(?string $package = null): string
    {
        if ($package === null || $package === '') {
            return $this->get('~apps');
        }

        return $this->app($package) ?? $this->get('~apps/' . $package);
    }

    /**
     * Project config directory (~config, legacy ~system).
     */
    public function system(string $path = ''): string
    {
        $alias = $path === '' ? '~config' : '~config/' . ltrim($path, '/');

        return $this->get($alias);
    }

    /**
     * Framework core directory (~pincore).
     */
    public function pincore(string $path = ''): string
    {
        return $this->get($path === '' ? '~pincore' : '~pincore/' . ltrim($path, '/'));
    }

    /**
     * Get path app
     */
    public function app(?string $packageName = null): ?string
    {
        $packageName = $packageName ?? $this->package;

        try {
            return $this->appEngine->path($packageName);
        } catch (\Exception) {
        }

        return null;
    }

    /**
     * Resolve a reference to an absolute filesystem path.
     *
     * Supported forms:
     * - ~, ~system, ~pincore, ~apps/{package}
     * - {package}:relative/path
     * - relative/path (active app)
     */
    public function get(string|ReferenceInterface $path = '', ?string $package = ''): string
    {
        $parser = $this->reference($path);
        $package = $package !== '' && $package !== null ? $package : $parser->getPackageName();
        $value = $parser->getValue() ?? '';
        $key = ($package ?? '') . ':' . $value;

        if (isset($this->paths[$key])) {
            return $this->paths[$key];
        }

        if ($package === '~' && ($value === 'pincore' || str_starts_with($value, 'pincore/'))) {
            $corePath = defined('PINOOX_CORE_PATH')
                ? rtrim(str_replace('\\', '/', \PINOOX_CORE_PATH), '/')
                : rtrim($this->basePath, '/') . '/pincore';
            $suffix = ltrim(substr($value, strlen('pincore')), '/');

            return $this->paths[$key] = $suffix !== '' ? $corePath . '/' . $suffix : $corePath;
        }

        if ($package === '~' && ($systemPath = SystemApp::stripPathAlias($value)) !== null) {
            return $this->paths[$key] = SystemApp::path($systemPath);
        }

        if ($package === '~' && ($value === 'apps' || str_starts_with($value, 'apps/'))) {
            $appsRoot = SystemConfig::path('apps');
            $suffix = $value === 'apps' ? '' : substr($value, strlen('apps/'));

            return $this->paths[$key] = $suffix === '' ? $appsRoot : $appsRoot . '/' . $suffix;
        }

        if ($package === '~' && $value !== '') {
            return $this->paths[$key] = SystemConfig::resolvePath('~/' . ltrim($value, '/'));
        }

        if ($package === SystemApp::PACKAGE || $package === SystemApp::LEGACY_PACKAGE) {
            return $this->paths[$key] = $this->systemPath($value);
        }

        $pathManager = $this->getManager($package);
        $relative = $value !== '' ? $value : '';

        return $this->paths[$key] = $pathManager->get($relative);
    }

    /**
     * Resolve a named reference, optionally remapping root (~) refs into a default package folder.
     */
    public function resolve(string|ReferenceInterface $fileName, string $defaultPackage = 'pincore'): string
    {
        $reference = $this->reference($fileName);
        $pathMain = $reference->getPackageName() === '~'
            ? $defaultPackage . '/' . $reference->getValue()
            : $reference->getValue();

        return $this->get(NameReference::create($reference->getPackageName(), $pathMain));
    }

    public function params(string|ReferenceInterface $path = '', ?string $package = ''): string
    {
        $basePath = $this->root();
        $resolved = $this->get($path, $package);
        $resolved = Str::firstDelete($resolved, $basePath);

        return Str::firstDelete($resolved, '/');
    }

    public function set(string $key, string $value): static
    {
        $this->paths[$key] = $value;

        return $this;
    }

    public function parse(string $name): ReferenceInterface
    {
        return $this->parser->parse($name);
    }

    public function prefixName(string|ReferenceInterface $path, string $prefix): string
    {
        return $this->prefixReference($path, $prefix)->get();
    }

    public function prefix(string|ReferenceInterface $path, string $prefix): string
    {
        return $this->get($this->prefixReference($path, $prefix));
    }

    public function prefixReference(string|ReferenceInterface $path, string $prefix): ReferenceInterface
    {
        $ref = $this->reference($path);
        $prefixed = $prefix . '/' . $ref->getValue();

        return NameReference::create($ref->getPackageName(), $prefixed);
    }

    public function reference(string|ReferenceInterface $path): ReferenceInterface
    {
        if (!$path instanceof ReferenceInterface) {
            $path = $this->parser->parse($path);
        }

        return $path;
    }

    private function systemPath(string $value): string
    {
        foreach ([
            SystemConfig::rawPath('app_config', 'config') => 'project_config',
            SystemConfig::rawPath('app_lang', 'lang') => 'platform_lang',
            SystemConfig::rawPath('app_migrations', 'database/migrations') => 'platform_migrations',
            SystemConfig::rawPath('app_seed', 'database/seed') => 'platform_seed',
            SystemConfig::rawPath('app_patches', 'patches') => 'platform_patches',
            'Model' => 'platform_models',
        ] as $folder => $pathKey) {
            $folder = trim($folder, '/');

            if ($value === $folder) {
                return SystemConfig::path($pathKey);
            }

            if (str_starts_with($value, $folder . '/')) {
                return SystemConfig::path($pathKey) . '/' . substr($value, strlen($folder) + 1);
            }
        }

        return SystemApp::path($value);
    }

    private function getManager(?string $packageName = null): PathManager
    {
        $pathManager = new PathManager();

        if (empty($this->package) || $packageName === '~') {
            $pathManager->setBasePath($this->basePath);
        } elseif ($packageName && $this->appEngine->exists($packageName)) {
            $pathManager->setBasePath($this->appEngine->path($packageName));
        } else {
            $pathManager->setBasePath($this->appEngine->path($this->package));
        }

        return $pathManager;
    }
}

