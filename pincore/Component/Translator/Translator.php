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
use Illuminate\Translation\Translator as TranslatorIlluminate;

class Translator extends TranslatorIlluminate
{
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
}