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


namespace Pinoox\Component\Translator;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator as TranslatorIlluminate;

class Translator extends TranslatorIlluminate
{
    public function addPath($path): void
    {
        $this->loader->addPath($path);
    }

    public function replaceNested($key, array $replace = [], $locale = null, $fallback = true): string
    {
        $string = $this->get($key, [], $locale, $fallback);
        return $this->makeReplaceNested($string, $replace);
    }

    protected function extractPlaceholders($text)
    {
        $pattern = '/:(\w+(?:\.\w+)*)/m';
        preg_match_all($pattern, $text, $matches);
        return $matches[1];
    }

    protected function getNestedValue(mixed $array, string $key)
    {
        if (!is_array($array))
            return null;
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

    protected function makeReplaceNested($text, $data)
    {
        $shouldReplace = [];
        $placeholders = $this->extractPlaceholders($text);
        foreach ($placeholders as $key) {
            $value = $this->getNestedValue($data, $key);
            $shouldReplace[':' . Str::ucfirst($key ?? '')] = Str::ucfirst($value ?? '');
            $shouldReplace[':' . Str::upper($key ?? '')] = Str::upper($value ?? '');
            $shouldReplace[':' . $key] = $value;
        }

        return strtr($text, $shouldReplace);
    }

    public function addJsonPath($path): void
    {
        $this->loader->addJsonPath($path);
    }

    protected function getLine($namespace, $group, $locale, $item, array $replace): string|array|null
    {
        $this->load($namespace, $group, $locale);


        if (empty($item))
            $line = $this->loaded[$namespace][$group][$locale];
        else
            $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            array_walk_recursive($line, function (&$value, $key) use ($replace) {
                $value = $this->makeReplacements($value, $replace);
            });

            return $line;
        }
        return null;
    }

    public function addPathAndJson(string $path): void
    {
        $this->addPath($path);
        $this->addJsonPath($path);
    }

    public function existsLocale($locale): bool
    {
        return $this->loader->existsLocale($locale);
    }
}