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


namespace pinoox\component\lang\source;

class FileLangSource extends LangSource
{
    private string $ext;

    public function __construct(string $path = '', string $locale = 'en', string $ext = '.lang.php')
    {
        $this->ext = $ext;
        $this->setLocale($locale);
        $this->setPath($path);
    }

    public function load(): ?string
    {
        return $this->getLocalePath();
    }

    public function get(string $key): array
    {
        return $array = $this->getArrayFromFile($key);
    }

    public function getArrayFromFile($key): ?array
    {
        $this->extractKey($key);
        $file = $this->getLocalePath() . $this->getFilename() . $this->ext;
        return file_exists($file) ? include_once $file : null;
    }


}