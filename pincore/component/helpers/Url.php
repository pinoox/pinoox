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
use pinoox\component\package\parser\UrlParser;
use pinoox\component\package\reference\ReferenceInterface;
use pinoox\component\package\reference\UrlReference;

class Url
{
    /**
     * @var string[]
     */
    private array $urls = [];

    /**
     * @var string|null
     */
    private ?string $packageName;

    public function __construct(
        private UrlParser       $parser,
        private EngineInterface $appEngine,
        private ?string         $baseUrl
    )
    {
        $this->packageName = $this->parser->getPackageName();
    }

    public function set($key, $value): Url
    {
        $this->urls[$key] = $value;
        return $this;
    }

    /**
     * Get url app
     *
     * @param string|null $packageName
     * @return string|null
     */
    public function app(?string $packageName = null): ?string
    {
        $packageName = !is_null($packageName) ? $packageName : $this->packageName;
        try {
            return $this->appEngine->url($packageName);
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * Get url app
     *
     * @return string
     */
    private function base(): string
    {
        return $this->baseUrl;
    }

    /**
     * Replaces specified characters in a URL string with a forward slash (/).
     * @param string $url The original URL string.
     * @param array|string $search The character(s) to be replaced, specified as an array or string.
     * @return string Returns a new string with the specified characters replaced by a forward slash.
     */
    public function replaceToSlash(string $url, array|string $search = ['\\', '>']): string
    {
        return str_replace($search, '/', $url);
    }

    public function prefix(string|ReferenceInterface $url, string $prefix): string
    {
        $reference = $this->prefixReference($url, $prefix);
        return $this->get($reference);
    }

    public function parse(string $name): ReferenceInterface
    {
        return $this->parser->parse($name);
    }

    public function reference(string|ReferenceInterface $url): ReferenceInterface
    {
        if (!($url instanceof ReferenceInterface))
            $url = $this->parser->parse($url);

        return $url;
    }

    /**
     * Get url
     *
     * @param string|ReferenceInterface $url
     * @return string
     * @throws \Exception
     */
    public function get(string|ReferenceInterface $url = ''): string
    {
        $parser = $this->reference($url);
        $key = $parser->get();

        if (isset($this->urls[$key]))
            return $this->urls[$key];

        $baseUrl = $this->getBaseUrl($parser->getPackageName());
        $value = !empty($parser->getPath()) ? $baseUrl . '/' . $parser->getPath() : $baseUrl;
        $value = $this->replaceToSlash($value);
        return $this->urls[$key] = $value;
    }

    public function prefixReference(string|ReferenceInterface $url, string $prefix): ReferenceInterface
    {
        $ref = $this->reference($url);

        $url = $prefix . '/' . $ref->getPath();

        return UrlReference::create(
            $ref->getPackageName(),
            $url);
    }

    public function prefixName(string|ReferenceInterface $url, string $prefix): string
    {
        $reference = $this->prefixReference($url, $prefix);
        return $reference->get();
    }

    private function getBaseUrl(?string $packageName = null): string
    {
        if ($packageName === '~') {
            $baseUrl = $this->baseUrl;
        } else if ($packageName === 'pincore') {
            $baseUrl = $this->baseUrl . 'pincore' . DIRECTORY_SEPARATOR;
        } else if (is_null($packageName) && $this->appEngine->exists($this->packageName)) {
            $baseUrl = $this->appEngine->url($this->packageName);
        } else if ($packageName && $this->appEngine->exists($packageName)) {
            $baseUrl = $this->appEngine->url($packageName);
        } else {
            throw new \Exception('file not found!');
        }

        return Str::lastDelete($baseUrl, DIRECTORY_SEPARATOR);
    }
}