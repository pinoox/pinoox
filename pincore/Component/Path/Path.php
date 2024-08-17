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

class Path implements PathInterface
{
    /**
     * @var string[]
     */
    private array $paths = [];

    public function __construct(
        private readonly string          $basePath,
        private readonly ParserInterface $parser,
        private readonly EngineInterface $appEngine,
        private string                   $package
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
    public function get(string|ReferenceInterface $path = '', string $package = ''): string
    {
        $parser = $this->reference($path);
        $package = empty($package) ? $parser->getPackageName() : $package;
        $key = $package . ':' . $parser->getValue();

        if (isset($this->paths[$key]))
            return $this->paths[$key];

        $pathManager = $this->getManager($package);
        $value = !empty($parser->getValue()) ? $parser->getValue() : '';
        $value = $pathManager->get($value);
        return $this->paths[$key] = $value;
    }

    public function params(string|ReferenceInterface $path = '', string $package = ''): string
    {
        $basePath = $this->get('~');
        $path = $this->get($path, $package);
        $path = Str::firstDelete($path, $basePath);
        return Str::firstDelete($path, '/');
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
        } else if ($packageName && $this->appEngine->exists($packageName)) {
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

        $path = $prefix . '/' . $ref->getValue();

        return NameReference::create(
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