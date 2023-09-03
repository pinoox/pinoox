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


namespace pinoox\component\helpers;


use pinoox\component\package\engine\EngineInterface;
use pinoox\component\package\parser\PathParser;
use pinoox\component\package\reference\PathReference;
use pinoox\component\package\reference\ReferenceInterface;

class Path
{
    /**
     * @var string[]
     */
    private array $paths = [];

    /**
     * @var string|null
     */
    private ?string $packageName;

    public function __construct(
        private PathParser $parser,
        private EngineInterface $appEngine,
        private string $basePath
    )
    {
        $this->packageName = $this->parser->getPackageName();
    }


    /**
     * Get path app
     *
     * @param string|null $packageName
     * @return string|null
     */
    public function app(?string $packageName = null): ?string
    {
        $packageName = !is_null($packageName) ? $packageName : $this->packageName;
        try {
            return $this->appEngine->path($packageName);
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * Get path
     *
     * @param string|ReferenceInterface $path
     * @return string
     * @throws \Exception
     */
    public function get(string|ReferenceInterface $path = ''): string
    {
        $parser = $this->reference($path);
        $key = $parser->get();

        if (isset($this->paths[$key]))
            return $this->paths[$key];

        $basePath = $this->getBasePath($parser->getPackageName());
        $value = !empty($parser->getPath()) ? $basePath . '/' . $parser->getPath() : $basePath;
        $value = $this->ds($value);
        return $this->paths[$key] = $value;
    }

    public function set($key, $value): Path
    {
        $this->paths[$key] = $value;
        return $this;
    }


    private function getBasePath(?string $packageName = null): string
    {
        if ($packageName === '~') {
            $basePath = $this->basePath;
        } else if ($packageName === 'pincore') {
            $basePath = $this->basePath . 'pincore' . DIRECTORY_SEPARATOR;
        } else if (is_null($packageName) && $this->packageName && $this->appEngine->exists($this->packageName)) {
            $basePath = $this->appEngine->path($this->packageName);
        } else if ($packageName && $this->appEngine->exists($packageName)) {
            $basePath = $this->appEngine->path($packageName);
        } else {
            throw new \Exception('file not found!');
        }

        return Str::lastDelete($basePath, DIRECTORY_SEPARATOR);
    }


    /**
     * Convert string path by a directory separator
     *
     * @param string $path
     * @return string
     */
    public function ds(string $path): string
    {
        return str_replace(['/', '\\', '>'], DIRECTORY_SEPARATOR, $path);
    }

    public function parse(string $name): ReferenceInterface
    {
        return $this->parser->parse($name);
    }

    /**
     * Get path app
     *
     * @return string
     */
    private function base(): string
    {
        return $this->basePath;
    }

    public function prefixName(string|ReferenceInterface $path, string $prefix): string
    {
        $reference = $this->prefixReference($path, $prefix);
        return $reference->get();
    }

    public function prefix(string|ReferenceInterface $path, string $prefix): string
    {
        $reference = $this->prefixReference($path, $prefix);
        return $this->get($reference);
    }

    public function prefixReference(string|ReferenceInterface $path, string $prefix): ReferenceInterface
    {
        $ref = $this->reference($path);

        $path = $prefix . '/' . $ref->getPath();

        return PathReference::create(
            $ref->getPackageName(),
            $path);
    }

    public function reference(string|ReferenceInterface $path)
    {
        if (!($path instanceof ReferenceInterface))
            $path = $this->parser->parse($path);

        return $path;
    }


}