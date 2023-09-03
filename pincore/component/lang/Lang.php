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


namespace pinoox\component\lang;

use pinoox\component\helpers\HelperArray;
use pinoox\component\kernel\Exception;
use pinoox\component\lang\source\LangSource;

class Lang
{

    private ?array $langArray;

    public function __construct(private LangSource $source)
    {
        $this->load();
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
     * @return mixed
     * @throws Exception
     */
    public function get(string $key, array $replacements = []): string
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

        return $this->replace($value, $replacements);
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

    private function replace(string $value, array $replacements): string
    {
        foreach ($replacements as $placeholder => $replacement) {
            $placeholder = '{' . $placeholder . '}';
            $value = str_replace($placeholder, $replacement, $value);
        }

        return $value;
    }


    function pluralize($key, $count)
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
    private function getValue(): ?string
    {
        if ($this->langArray === null) {
            throw new Exception('The language array is not set.');
        }

        return HelperArray::getNestedValue($this->langArray, $this->source->getKey());
    }

}