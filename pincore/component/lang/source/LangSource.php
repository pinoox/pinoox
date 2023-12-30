<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component\Lang\Source;


/**
 * The Abstract Class defines the contract for loading and saving language data from different sources.
 * @package Pinoox\Component\Lang\Source
 */
abstract class LangSource
{
    private string $locale = 'en';
    protected string $fallback = 'en';
    private string $filename;
    private string $key;
    private string $path;

    abstract public function __construct(string $path, string $locale);

    /**
     * Load data from a source.
     * @return mixed
     */
    abstract public function load(): mixed;

    /**
     * Get language array by key
     *
     * @param string $key
     * @return mixed
     */
    abstract public function get(string $key): mixed;

    public function getLocalePath(): string
    {
        return $this->getPath() . '/' . $this->getLocale() . '/';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setFallback(string $lang): void
    {
        $this->fallback = $lang;
    }


    public function getFallback(): ?string
    {
        return $this->fallback;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    public function setFilename(string $name): void
    {
        $this->filename = $name;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    protected function extractKey(string $str): void
    {
        $parts = explode('.', $str);
        $this->setFilename($parts[0] ?? null);
        $this->setKey(str_replace($this->getFilename() . '.', '', $str));
    }

}