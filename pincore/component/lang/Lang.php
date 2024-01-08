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


namespace Pinoox\Component\Lang;

use Pinoox\Component\Helpers\HelperArray;
use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Lang\Source\LangSource;

class Lang
{

    private ?array $langArray;
    private LangSource $source;

    public function __construct(?LangSource $source = null)
    {
        if (!empty($source))
            $this->create($source);
    }

    public function create(LangSource $source): static
    {
        $this->source = $source;
        $this->load();

        return $this;
    }

    public function load(): void
    {
        $this->source->load();
    }

    public function locale($locale): void
    {
        $this->source->setLocale($locale);
    }

    /**
     * @param string $key
     * @param array $replacements
     * @return string|array
     * @throws Exception
     */
    public function get(string $key, array $replacements = []): string|array
    {
        $this->langArray = $this->source->get($key);
        $value = $this->getValue();
        if (empty($value) && !empty($this->source->getFallback())) {
            $this->source->setLocale($this->source->getFallback());
            $this->langArray = $this->source->get($key);
            $value = $this->getValue();
        }

        if (!$value) {
            throw new Exception('The key is not found in the language: ' . $key);
        }

        return is_array($value) ? $value : $this->replace($value, $replacements);
    }

    /**
     * @throws Exception
     */
    public function getChoice(string $key, $count = 0): string
    {
        $value = $this->get($key);

        $pattern = '/^(.*?) \| (.*?) \| (.*)$/';
        if (preg_match($pattern, $value, $matches)) {
            if ($count == 0) {
                return $matches[1];
            } else if ($count === 1) {
                return $matches[2];
            } else {
                return str_replace(':count', $count, $matches[3]);
            }
        }

        return $key;
    }

    public function setFallback($lang): void
    {
        $this->source->setFallback($lang);
    }

    public function getFallback($lang): ?string
    {
        return $this->source->getFallback($lang);
    }

    private function replace(string $value, array $replacements): string
    {
        foreach ($replacements as $placeholder => $replacement) {
            $placeholder = '{' . $placeholder . '}';
            $value = str_replace($placeholder, $replacement, $value);
        }

        return $value;
    }


    public function pluralize($key, $count)
    {
        if (isset($translations[$key])) {
            $translation = $translations[$key];
            $pattern = '/^(.*?) \| (.*?) \| (.*)$/';

            if (preg_match($pattern, $translation, $matches)) {
                if ($count === 1) {
                    return $matches[2];
                } else {
                    return str_replace(':count', $count, $matches[3]);
                }
            }
        }

        // Handle missing translations
        return $key;
    }


    /**
     * @throws Exception
     */
    private function getValue(): string|array|null
    {
        if ($this->langArray === null) {
            throw new Exception('The language array is not set.');
        }

        return $this->getNestedValue($this->langArray, $this->source->getKey());
    }

    public function getNestedValue(array $array, string $key)
    {
        if (empty($key))
            return $array;

        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $nestedKey) {

            if (isset($value[$nestedKey])) {
                $value = $value[$nestedKey];
            } else {
                return null;
            }
        }

        return $value;
    }

}