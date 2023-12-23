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


namespace pinoox\component\Path;

use pinoox\component\package\AppLayer;
use pinoox\component\package\engine\EngineInterface;
use pinoox\component\Path\parser\PathParser;
use pinoox\component\Path\reference\PathReference;
use pinoox\component\Path\reference\ReferenceInterface;
use pinoox\component\Path\Manager\PathManager;

class Path implements PathInterface
{
    /**
     * @var string[]
     */
    private array $paths = [];

    public function __construct(
        private readonly string          $basePath,
        private readonly PathParser      $parser,
        private readonly EngineInterface $appEngine,
        private readonly AppLayer        $appLayer
    )
    {
    }


    /**
     * Get path app
     *
     * @param string|null $packageName
     * @return string|null
     */
    public function app(?string $packageName = null): ?string
    {
        $packageName = !is_null($packageName) ? $packageName : $this->appLayer->getPackageName();
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
    public function get(string|ReferenceInterface $path = '',string $package = ''): string
    {
        $parser = $this->reference($path);
        $package = empty($package)? $parser->getPackageName() : $package;
        $key = $parser->get();

        if (isset($this->paths[$key]))
            return $this->paths[$key];

        $pathManager = $this->getManager($package);
        $value = !empty($parser->getPath()) ? $parser->getPath() : '';
        $value = $pathManager->get($value);
        return $this->paths[$key] = $value;
    }

    public function set($key, $value): static
    {
        $this->paths[$key] = $value;
        return $this;
    }


    /**
     * @throws \Exception
     */
    private function getManager(?string $packageName = null): PathManager
    {
        $pathManager = new PathManager();
        $currentPackage = $this->appLayer->getPackageName();
        if ($packageName === '~') {
            $pathManager->setBasePath($this->basePath);
        } else if (is_null($packageName) && $currentPackage && $this->appEngine->exists($currentPackage)) {
            $pathManager->setBasePath($this->appEngine->path($currentPackage));
        } else if ($packageName && $this->appEngine->exists($packageName)) {
            $pathManager->setBasePath($this->appEngine->path($packageName));
        } else {
            throw new \Exception('file not found!');
        }

        return $pathManager;
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

    /**
     * @throws \Exception
     */
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

    public function reference(string|ReferenceInterface $path): ReferenceInterface
    {
        if (!($path instanceof ReferenceInterface))
            $path = $this->parser->parse($path);

        return $path;
    }


}