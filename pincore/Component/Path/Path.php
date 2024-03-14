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

use Pinoox\Component\Package\Engine\EngineInterface;
use Pinoox\Component\Path\Parser\ParserInterface;
use Pinoox\Component\Path\Parser\PathParser;
use Pinoox\Component\Path\Reference\PathReference;
use Pinoox\Component\Path\Reference\ReferenceInterface;
use Pinoox\Component\Path\Manager\PathManager;

class Path implements PathInterface
{
    /**
     * @var string[]
     */
    private array $paths = [];

    public function __construct(
        private readonly string          $basePath,
        private readonly ParserInterface      $parser,
        private readonly EngineInterface $appEngine,
        private string        $package
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
        $packageName = !is_null($packageName) ? $packageName : $this->package;
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
     * @param string $package
     * @return string
     * @throws \Exception
     */
    public function get(string|ReferenceInterface $path = '',string $package = ''): string
    {
        $parser = $this->reference($path);
        $package = empty($package)? $parser->getPackageName() : $package;
        $key = $package . ':' . $parser->getPath();

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

    private function getManager(?string $packageName = null): PathManager
    {
        $pathManager = new PathManager();
        if ($packageName === '~') {
            $pathManager->setBasePath($this->basePath);
        }  else if ($packageName && $this->appEngine->exists($packageName)) {
            $pathManager->setBasePath($this->appEngine->path($packageName));
        } else {
            $pathManager->setBasePath($this->appEngine->path($this->package));
        }

        return $pathManager;
    }

    public function parse(string $name): ReferenceInterface
    {
        return $this->parser->parse($name);
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